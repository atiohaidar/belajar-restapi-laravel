<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactCreateRequest;
use App\Http\Requests\ContactUpdateRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\ContactResourceCollection;
use App\Models\Contact;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * PENTING BANGET
     * kalau misal dari query ORM itu ga muncul, bisa jadi emang deefault nya engga bisa, paling harus make ->get(). msial query
     */
    /**
     * Summary of checkContact
     * @param \App\Models\Contact|null $contact
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     * @return bool
     */
    private function checkContact(Contact $contact = null)
    {
        if (!isset($contact)) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["not found"]
                ]
            ])->setStatusCode(404));
        }
        return true;
    }
    private function searchContactFromQuery($query, $user_id)
    {
        $contact = Contact::query()->where("user_id", $user_id);

        if ($query["name"]) {
            $contact = $contact->where(function (Builder $builder) use ($query) {
                $builder->orwhere("firstname", "like", "%" . $query["name"] . "%");
                $builder->orWhere("lastname", "like", "%" . $query["name"]."%" );
            });
        }
        if ($query["email"]) {
            $contact = $contact->where("email", "like", "%" . $query["email"] . "%");
        }
        if ($query["phone"]) {
            $contact = $contact->where("phone", "like", "%" . $query["phone"] . "%");
        }

        return $contact;




    }
    /**
     * digunakn untuk membuat kontak
     * @param \App\Http\Requests\ContactCreateRequest $request
     * @return void
     * step
     * 1. validasi data $ ambil data
     * 2. cek apakah data ini milik user yang sedang login
     * 3. simpan data
     */
    public function create(ContactCreateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = Auth::user();
        $contact = new Contact($data);
        $contact->user_id = $user->id;
        $contact->save();
        return (new ContactResource($contact))->response()->setStatusCode(201);


    }
    public function update(int $id, ContactUpdateRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        // karena syaratknya itu harus milik usernya, kalo engga berarti ga bisa update
        $contact = Contact::where("id", $id)->where("user_id", $user->id)->first();
        $this->checkContact($contact);
        $contact->fill($data);
        $contact->save();
        return new ContactResource($contact);


    }
    public function get(int $id, Request $request)
    {
        $user = Auth::user();
        
        $contact = Contact::where("id", $id)->where("user_id", $user->id)->first();
        print_r(value: json_encode($contact, JSON_PRETTY_PRINT));

        $this->checkContact($contact);
        return new ContactResource($contact);
        // return response()->json(["data"=>$contact]);
    }
    public function delete(int $id, Request $request)
    {
        $user = Auth::user();
        $contact = Contact::where("id", $id)->where("user_id", $user->id)->first();
        $this->checkContact($contact);
        $contact->delete();
        return response()->json(["data" => true]);
    }

    public function list(Request $request)
    {
        $user = Auth::user();
       
        $size = $request->input("size", 10);
        $page = $request->input("page", 1);
        $queryFromUser = [
            "name" => $request->input("name"),
            "email" => $request->input("email"),
            "phone" => $request->input("phone"),
        ];
        $contacts = $this->searchContactFromQuery($queryFromUser, $user->id);
        $contacts = $contacts->paginate($size, ["*"], "page", $page);

        
        // return response()->json(["data"=> $contacts]); //sebenernya kayak gini bisa, cuman kurang rapi aja, jadi engga dibungkus meta bagian pagination nya
        return new ContactResourceCollection($contacts);



    }

}
