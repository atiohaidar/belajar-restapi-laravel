<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactCreateRequest;
use App\Http\Requests\ContactUpdateRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Auth;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
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
        if (!isset($contact)) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["not found"]
                ]
            ])->setStatusCode(404));
        }
        $contact->fill($data);
        $contact->save();
        return new ContactResource($contact);


    }
    public function get(int $id, Request $request)
    {
        $user = Auth::user();
        $contact = Contact::where("id", $id)->where("user_id", $user->id)->first();
        if (!isset($contact)) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "not found"
                    ]
                ]
            ])->setStatusCode(404));
        }
        return new ContactResource($contact);
    }
    public function delete(int $id, Request $request)
    {

    }

    public function list(Request $request)
    {
    }

}
