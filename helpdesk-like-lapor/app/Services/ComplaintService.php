<?php
// File: app/Services/ComplaintService.php

namespace App\Services; // <-- Pastikan namespace benar

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintLog;
use App\Models\ComplaintTransfer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as SystemLog; // Alias system log
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComplaintService
{
    /**
     * Create a new complaint with attachments and initial log.
     *
     * @param array $validatedData Data from StoreComplaintRequest
     * @param User $user The reporter user
     * @return Complaint
     * @throws \Exception If file storage fails
     */
    public function createComplaint(array $validatedData, User $user): Complaint
    {
        return DB::transaction(function () use ($validatedData, $user) {
            // 1. Create Complaint
            $complaint = Complaint::create([
                'user_id' => $user->id,
                'category_id' => $validatedData['category_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'priority' => $validatedData['priority'] ?? 'Medium',
                'status' => 'Unprocessed', // Initial status
                'agency_id' => null, // Initially unassigned
            ]);

            // 2. Handle Attachments
            if (!empty($validatedData['attachments'])) {
                $this->storeAttachments($complaint, $validatedData['attachments']);
            }

            // 3. Create Initial Log
            $this->addLog($complaint, $user, 'Created', 'Complaint filed.');

            // 4. Optional: Dispatch Event for Notifications
            // event(new \App\Events\ComplaintCreated($complaint));

            return $complaint;
        });
    }

     /**
      * Update complaint status, priority, or assignment.
      * Adds log entries for changes.
      *
      * @param Complaint $complaint
      * @param array $validatedData Data from UpdateComplaintRequest
      * @param User $updater User performing the update
      * @return Complaint
      */
     public function updateComplaint(Complaint $complaint, array $validatedData, User $updater): Complaint
     {
         return DB::transaction(function() use ($complaint, $validatedData, $updater) {
             $originalStatus = $complaint->status;
             $originalPriority = $complaint->priority;
             $originalAgencyId = $complaint->agency_id;
             $originalAgencyName = $complaint->agency?->name ?? 'Unassigned'; // Get name before update

             // Prepare update array (only validated fields)
             $updateData = [];
             if (isset($validatedData['status'])) $updateData['status'] = $validatedData['status'];
             if (isset($validatedData['priority'])) $updateData['priority'] = $validatedData['priority'];
              // Only allow agency update if it's in validated data (and permitted by request rules/policy)
              if (array_key_exists('agency_id', $validatedData)) { // Check existence, allows setting to null
                  $updateData['agency_id'] = $validatedData['agency_id'];
              }

             if (!empty($updateData)) {
                 $complaint->update($updateData);
                 $complaint->refresh(); // Refresh to get updated state

                 // Add Logs for specific changes
                 if (isset($updateData['status']) && $updateData['status'] !== $originalStatus) {
                     $this->addLog($complaint, $updater, 'Status Update', "Status changed from {$originalStatus} to {$updateData['status']}.");
                     // Optional: Dispatch Status Update Event
                     // event(new \App\Events\ComplaintStatusUpdated($complaint, $originalStatus));
                 }
                 if (isset($updateData['priority']) && $updateData['priority'] !== $originalPriority) {
                     $this->addLog($complaint, $updater, 'Priority Change', "Priority changed from {$originalPriority} to {$updateData['priority']}.");
                 }
                 if (array_key_exists('agency_id', $updateData) && $updateData['agency_id'] !== $originalAgencyId) {
                     $newAgencyName = $complaint->agency?->name ?? 'Unassigned';
                     $action = $originalAgencyId === null ? 'Assigned' : 'Transferred';
                     $details = $action === 'Assigned'
                               ? "Assigned to Agency: {$newAgencyName}."
                               : "Transferred from Agency: {$originalAgencyName} to Agency: {$newAgencyName}.";
                     $this->addLog($complaint, $updater, $action, $details);
                      // Optional: Dispatch Assignment/Transfer Event
                      // event(new \App\Events\ComplaintAssigned($complaint, $originalAgencyId));
                 }
             }

             return $complaint;
         });
     }

      /**
       * Store uploaded attachments for a complaint.
       *
       * @param Complaint $complaint
       * @param array<UploadedFile> $files
       * @return void
       * @throws \Exception
       */
      public function storeAttachments(Complaint $complaint, array $files): void
      {
          foreach ($files as $file) {
              try {
                  // Generate unique path: complaints/{complaint_id}/{uuid}.{extension}
                  $extension = $file->getClientOriginalExtension();
                  $filename = Str::uuid() . '.' . $extension;
                  $path = $file->storeAs("complaints/{$complaint->id}", $filename, 'public'); // Use 'public' disk

                  if ($path === false) {
                      throw new \Exception("Failed to store file: " . $file->getClientOriginalName());
                  }

                  ComplaintAttachment::create([
                      'complaint_id' => $complaint->id,
                      'file_path' => $path, // Store path relative to disk root
                      'file_name' => $file->getClientOriginalName(),
                      'mime_type' => $file->getMimeType(),
                      'uploaded_at' => now(),
                  ]);

              } catch (\Exception $e) {
                  SystemLog::error("Attachment upload failed for complaint {$complaint->id}: " . $e->getMessage());
                  // Clean up already stored files for this request if needed (more complex)
                  throw $e; // Re-throw to fail the transaction
              }
          }
      }

    /**
     * Add a log entry for a complaint.
     *
     * @param Complaint $complaint
     * @param User|null $user User performing the action (null for system)
     * @param string $action Action description (e.g., 'Created', 'Status Update')
     * @param string|null $details Additional details
     * @return ComplaintLog
     */
    public function addLog(Complaint $complaint, ?User $user, string $action, ?string $details = null): ComplaintLog
    {
        return ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'user_id' => $user?->id,
            'action' => $action,
            'details' => $details,
            'timestamp' => now(),
        ]);
    }
    /**
     * Transfer a complaint to a new agency.
     * Updates the complaint, creates transfer record, and adds log.
     *
     * @param Complaint $complaint
     * @param array $validatedData ['to_agency_id', 'reason']
     * @param User $initiator User initiating the transfer
     * @return Complaint The updated complaint instance
     */
    public function transferComplaint(Complaint $complaint, array $validatedData, User $initiator): Complaint
    {
        return DB::transaction(function () use ($complaint, $validatedData, $initiator) {
            $fromAgencyId = $complaint->agency_id; // Get current agency ID before update
            $fromAgencyName = $complaint->agency?->name ?? 'Unassigned';
            $toAgencyId = $validatedData['to_agency_id'];
            $reason = $validatedData['reason'] ?? null;

            // 1. Update Complaint's Agency
            $complaint->update(['agency_id' => $toAgencyId]);
            $complaint->refresh(); // Get updated state including new agency relation if needed
            $toAgencyName = $complaint->agency?->name ?? 'Unknown'; // Get name after update

            // 2. Create Transfer Record
            ComplaintTransfer::create([
                'complaint_id' => $complaint->id,
                'from_agency_id' => $fromAgencyId,
                'to_agency_id' => $toAgencyId,
                'user_id' => $initiator->id,
                'reason' => $reason,
            ]);

            // 3. Add Log Entry
            $logDetails = "Transferred from Agency: {$fromAgencyName} to Agency: {$toAgencyName}.";
            if ($reason) {
                $logDetails .= " Reason: {$reason}";
            }
            $this->addLog($complaint, $initiator, 'Transferred', $logDetails);

            // 4. Optional: Dispatch Transfer Event for Notifications
            // event(new \App\Events\ComplaintTransferred($complaint, $fromAgencyId, $toAgencyId));

            return $complaint;
        });
    }
}