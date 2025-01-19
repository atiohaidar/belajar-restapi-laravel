<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressCreateRequest;
use App\Http\Requests\AddressUpdateRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Auth;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    private function fetchDataContact(User $user, int $idContact)
    {
        $contact = Contact::where("user_id", $user->id)->where("id", $idContact)->first();
        if (!isset($contact)) {
            throw new HttpResponseException(response()->json([
                "errors" => [
                    "message" => [
                        "contact not found"
                    ]
                ]
            ])->setStatusCode(404));
        }
        return $contact;
    }
    private function fetchDataAddress(Contact $contact, int $idAddress)
    {
        $address = Address::where("contact_id", $contact->id)->where("id", $idAddress)->first();
        if (!isset($address)) {
            throw new HttpResponseException(response()->json([

                "errors" => [
                    "message" => [
                        "address not found"
                    ]
                ]
            ])->setStatusCode(404));
        }
        return $address;


    }
    private function checkContact(Contact $contact = null)
    {
        if (!isset($contact)) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["contact not found"]
                ]
            ])->setStatusCode(404));
        }
        return true;
    }
    public function create(int $idContact, AddressCreateRequest $request)
    {
        $user = Auth::user();
        $contact = Contact::where('user_id', $user->id)->where("id", $idContact)->first();
        $this->checkContact($contact);
        $data = $request->validated();
        $address = new Address($data);
        $address->contact_id = $contact->id;
        $address->save();
        return (new AddressResource($address))->response()->setStatusCode(201);
    }
    public function get(int $idContact, int $idAddress, Request $request)
    {
        $user = Auth::user();
        $contact = $this->fetchDataContact($user, $idContact);
        $address = $this->fetchDataAddress($contact, $idAddress);
        return new AddressResource($address);
    }
    public function update(int $idContact, int $idAddress, AddressUpdateRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $contact = $this->fetchDataContact($user, $idContact);
        $address = $this->fetchDataAddress($contact, $idAddress);
        $address->fill($data);
        $address->save();
        return new AddressResource($address);
    }
    public function list(int $idContact, Request $request)
    {
        $user = Auth::user();
        $contact = $this->fetchDataContact($user, $idContact);
        $addresses = Address::where("contact_id", $contact->id)->get();
        return AddressResource::collection($addresses);
    }
    public function delete(int $idContact, int $idAddress, Request $request)
    {
        $user = Auth::user();
        $contact = $this->fetchDataContact($user, $idContact);
        $address = $this->fetchDataAddress($contact, $idAddress);
        $address->delete();
        return response()->json([
            "data" => true
        ]);
    }
}
