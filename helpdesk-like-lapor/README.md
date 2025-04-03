# iya tau ini make laravel
kode plauml nya?
```
@startuml

entity "User" as user {
  +id: TEXT [PK]  -- UUID stored as TEXT
  --
  +name: TEXT NOT NULL
  +username: TEXT UNIQUE NOT NULL
  +password: TEXT NOT NULL  -- Store hashed password
  +email: TEXT UNIQUE NOT NULL
  +phone: TEXT
  +role: TEXT NOT NULL CHECK (role IN ('Admin', 'Agency Manager', 'Reporter'))
  +last_login: TEXT -- ISO8601 format
  +last_active: TEXT -- ISO8601 format
  +agency_id: TEXT [FK -> agency.id] -- User belongs to an Agency (optional for Admin/Reporter?)
}

entity "Agency" as agency {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +name: TEXT NOT NULL
  +address: TEXT
  +email: TEXT
  +phone: TEXT
  +parent_id: TEXT [FK -> agency.id] -- Self-referencing for hierarchy
}

entity "Complaint" as complaint {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +user_id: TEXT [FK -> user.id] -- User who filed the complaint
  +agency_id: TEXT [FK -> agency.id] -- Agency currently assigned
  +category_id: TEXT [FK -> category.id]
  +title: TEXT NOT NULL -- Added Title
  +description: TEXT NOT NULL
  +status: TEXT NOT NULL CHECK (status IN ('Unprocessed', 'Pending', 'In Progress', 'Resolved', 'Archived')) DEFAULT 'Unprocessed'
  +priority: TEXT NOT NULL CHECK (priority IN ('Low', 'Medium', 'High')) DEFAULT 'Medium'
  +created_at: TEXT NOT NULL -- ISO8601 format
}

entity "Complaint Category" as category {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +name: TEXT UNIQUE NOT NULL
  +description: TEXT
}

entity "ComplaintAttachment" as attachment {
 +id: TEXT [PK] -- UUID stored as TEXT
 --
 +complaint_id: TEXT NOT NULL [FK -> complaint.id]
 +file_path: TEXT NOT NULL -- Path on server or URL to cloud storage
 +file_name: TEXT NOT NULL -- Original file name
 +mime_type: TEXT -- e.g., 'image/jpeg', 'application/pdf'
 +uploaded_at: TEXT NOT NULL -- ISO8601 format
}

entity "Notification" as notification {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +user_id: TEXT NOT NULL [FK -> user.id]
  +complaint_id: TEXT [FK -> complaint.id] -- Optional, notification might not be about a specific complaint
  +message: TEXT NOT NULL
  +is_read: INTEGER NOT NULL CHECK (is_read IN (0, 1)) DEFAULT 0 -- 0=false, 1=true
  +created_at: TEXT NOT NULL -- ISO8601 format
}

entity "Complaint Follow-Up" as followup {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +complaint_id: TEXT NOT NULL [FK -> complaint.id]
  +user_id: TEXT NOT NULL [FK -> user.id] -- User who performed the follow-up (likely Agency Manager/Admin)
  +agency_id: TEXT [FK -> agency.id] -- Agency context if needed, though user's agency might suffice
  +description: TEXT NOT NULL
  +created_at: TEXT NOT NULL -- ISO8601 format
}

entity "Complaint Transfer" as transfer {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +complaint_id: TEXT NOT NULL [FK -> complaint.id]
  +from_agency_id: TEXT [FK -> agency.id]
  +to_agency_id: TEXT NOT NULL [FK -> agency.id]
  +user_id: TEXT NOT NULL [FK -> user.id] -- User who initiated the transfer
  +reason: TEXT
  +created_at: TEXT NOT NULL -- ISO8601 format
}

entity "Rating" as rating {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +user_id: TEXT NOT NULL [FK -> user.id] -- User who gave the rating
  +agency_id: TEXT NOT NULL [FK -> agency.id] -- Agency being rated
  +complaint_id: TEXT [FK -> complaint.id] -- Optional: rating related to a specific resolved complaint
  +stars: INTEGER NOT NULL CHECK (stars >= 1 AND stars <= 5)
  +review: TEXT
  +created_at: TEXT NOT NULL -- ISO8601 format
}

entity "Complaint Log" as log {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +complaint_id: TEXT NOT NULL [FK -> complaint.id]
  +user_id: TEXT [FK -> user.id] -- User performing the action (can be null if system action)
  +action: TEXT NOT NULL -- e.g., "Created", "Status changed to Pending", "Assigned to Agency X"
  +details: TEXT -- Optional additional details
  +timestamp: TEXT NOT NULL -- ISO8601 format
}

entity "Comment" as comment {
  +id: TEXT [PK] -- UUID stored as TEXT
  --
  +complaint_id: TEXT NOT NULL [FK -> complaint.id]
  +user_id: TEXT NOT NULL [FK -> user.id] -- User who wrote the comment
  +message: TEXT NOT NULL
  +created_at: TEXT NOT NULL -- ISO8601 format
}

' Relationships
user "1" -- "*" complaint : "Reports >"
user "*" -- "1" agency : "< Works At / Manages" ' User associated with an Agency
user "1" -- "*" comment : "Writes >"
user "1" -- "*" followup : "Performs >"
user "1" -- "*" notification : "< Receives"
user "1" -- "*" log : "< Performed By"
user "1" -- "*" transfer : "Initiates >"
user "1" -- "*" rating : "Gives >"

agency "1" -- "*" agency : "Has Sub-Agencies >" ' Self-referencing for parent/child
agency "1" -- "*" user : "Employs / Has Member >"
agency "1" -- "*" complaint : "< Assigned To"
agency "1" -- "*" followup : "< Handled By"
agency "1" -- "*" transfer : "< Transferred From"
agency "1" -- "*" transfer : "< Transferred To"
agency "1" -- "*" rating : "< Rated"

complaint "1" -- "*" comment : "Has >"
complaint "1" -- "*" followup : "Has >"
complaint "1" -- "*" notification : "Relates To >"
complaint "1" -- "*" log : "History Of >"
complaint "1" -- "*" transfer : "Subject Of >"
complaint "1" -- "*" rating : "Basis For >"
complaint "1" -- "*" attachment : "Has Attachments >"
complaint "*" -- "1" category : "< Belongs To"
complaint "*" -- "1" user : "< Filed By"
complaint "*" -- "1" agency : "< Assigned To"


category "1" -- "*" complaint : "Categorizes >"

attachment "*" -- "1" complaint : "< Attached To"

@enduml
```
sql nya?
```
-- Enable Foreign Key support
PRAGMA foreign_keys = ON;

-- Table for Agencies (can have hierarchy)
CREATE TABLE Agency (
    id TEXT PRIMARY KEY, -- UUID
    name TEXT NOT NULL,
    address TEXT,
    email TEXT,
    phone TEXT,
    parent_id TEXT, -- Self-referencing FK for hierarchy
    FOREIGN KEY (parent_id) REFERENCES Agency(id) ON DELETE SET NULL -- Set parent to NULL if parent deleted
);

-- Table for Users
CREATE TABLE User (
    id TEXT PRIMARY KEY, -- UUID
    name TEXT NOT NULL,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL, -- Store hashed password!
    email TEXT UNIQUE NOT NULL,
    phone TEXT,
    role TEXT NOT NULL CHECK (role IN ('Admin', 'Agency Manager', 'Reporter')),
    last_login TEXT, -- ISO8601 Format e.g., '2023-10-27T10:00:00Z'
    last_active TEXT, -- ISO8601 Format
    agency_id TEXT, -- User might belong to an agency
    FOREIGN KEY (agency_id) REFERENCES Agency(id) ON DELETE SET NULL -- User remains if agency deleted
);

-- Table for Complaint Categories
CREATE TABLE ComplaintCategory (
    id TEXT PRIMARY KEY, -- UUID
    name TEXT UNIQUE NOT NULL,
    description TEXT
);

-- Table for Complaints
CREATE TABLE Complaint (
    id TEXT PRIMARY KEY, -- UUID
    user_id TEXT, -- User who filed the complaint (can be NULL if anonymous or user deleted)
    agency_id TEXT, -- Agency currently assigned (can be NULL if unassigned or agency deleted)
    category_id TEXT, -- Category of the complaint (can be NULL if category deleted)
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('Unprocessed', 'Pending', 'In Progress', 'Resolved', 'Archived')) DEFAULT 'Unprocessed',
    priority TEXT NOT NULL CHECK (priority IN ('Low', 'Medium', 'High')) DEFAULT 'Medium',
    created_at TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE SET NULL,
    FOREIGN KEY (agency_id) REFERENCES Agency(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES ComplaintCategory(id) ON DELETE SET NULL
);

-- Table for Complaint Attachments (Metadata)
CREATE TABLE ComplaintAttachment (
   id TEXT PRIMARY KEY, -- UUID
   complaint_id TEXT NOT NULL,
   file_path TEXT NOT NULL, -- Path on server or URL
   file_name TEXT NOT NULL, -- Original filename
   mime_type TEXT, -- e.g., 'image/jpeg'
   uploaded_at TEXT NOT NULL, -- ISO8601 Format
   FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE CASCADE -- Delete attachments if complaint deleted
);

-- Table for Notifications
CREATE TABLE Notification (
    id TEXT PRIMARY KEY, -- UUID
    user_id TEXT NOT NULL,
    complaint_id TEXT, -- Optional link to a complaint
    message TEXT NOT NULL,
    is_read INTEGER NOT NULL CHECK (is_read IN (0, 1)) DEFAULT 0, -- 0=false, 1=true
    created_at TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE, -- Delete notification if user deleted
    FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE SET NULL -- Keep notification if complaint deleted
);

-- Table for Complaint Follow-Ups
CREATE TABLE ComplaintFollowUp (
    id TEXT PRIMARY KEY, -- UUID
    complaint_id TEXT NOT NULL,
    user_id TEXT NOT NULL, -- User performing the follow-up
    agency_id TEXT, -- Agency context if needed
    description TEXT NOT NULL,
    created_at TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE CASCADE, -- Delete follow-up if complaint deleted
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE, -- Delete follow-up if user deleted
    FOREIGN KEY (agency_id) REFERENCES Agency(id) ON DELETE SET NULL
);

-- Table for Complaint Transfers
CREATE TABLE ComplaintTransfer (
    id TEXT PRIMARY KEY, -- UUID
    complaint_id TEXT NOT NULL,
    from_agency_id TEXT, -- Can be NULL if initially assigned from unassigned
    to_agency_id TEXT NOT NULL,
    user_id TEXT NOT NULL, -- User who initiated transfer
    reason TEXT,
    created_at TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE CASCADE,
    FOREIGN KEY (from_agency_id) REFERENCES Agency(id) ON DELETE SET NULL,
    FOREIGN KEY (to_agency_id) REFERENCES Agency(id) ON DELETE CASCADE, -- If target agency deleted, transfer history might be less relevant? Or SET NULL? Let's use CASCADE for simplicity here.
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE SET NULL -- Keep transfer record even if user deleted
);

-- Table for Ratings
CREATE TABLE Rating (
    id TEXT PRIMARY KEY, -- UUID
    user_id TEXT NOT NULL, -- User giving the rating
    agency_id TEXT NOT NULL, -- Agency being rated
    complaint_id TEXT, -- Optional: Rating associated with a specific complaint
    stars INTEGER NOT NULL CHECK (stars >= 1 AND stars <= 5),
    review TEXT,
    created_at TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (agency_id) REFERENCES Agency(id) ON DELETE CASCADE, -- Delete rating if agency deleted
    FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE SET NULL -- Keep rating if complaint deleted
);

-- Table for Complaint Logs (History)
CREATE TABLE ComplaintLog (
    id TEXT PRIMARY KEY, -- UUID using randomblob(16) or similar function in application layer
    complaint_id TEXT NOT NULL,
    user_id TEXT, -- Can be NULL for system actions
    action TEXT NOT NULL, -- e.g., 'Created', 'Status Update', 'Assigned'
    details TEXT, -- e.g., 'Status changed from Pending to In Progress'
    timestamp TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE SET NULL
);

-- Table for Comments on Complaints
CREATE TABLE Comment (
    id TEXT PRIMARY KEY, -- UUID
    complaint_id TEXT NOT NULL,
    user_id TEXT NOT NULL, -- User writing the comment
    message TEXT NOT NULL,
    created_at TEXT NOT NULL, -- ISO8601 Format
    FOREIGN KEY (complaint_id) REFERENCES Complaint(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_complaint_status ON Complaint(status);
CREATE INDEX idx_complaint_agency ON Complaint(agency_id);
CREATE INDEX idx_complaint_user ON Complaint(user_id);
CREATE INDEX idx_complaint_category ON Complaint(category_id);
CREATE INDEX idx_user_username ON User(username);
CREATE INDEX idx_notification_user_read ON Notification(user_id, is_read);
CREATE INDEX idx_log_complaint_time ON ComplaintLog(complaint_id, timestamp);
CREATE INDEX idx_comment_complaint_time ON Comment(complaint_id, created_at);
CREATE INDEX idx_attachment_complaint ON ComplaintAttachment(complaint_id);
```

sebenenrya untuk email tuh harus ada password app yna,, itu bisa cari di google. untuk queue, inii aga kompleks

Todo yang harus dipelajari
- Policy&Gate
- Queue
- Event Handler
- Broadcast
