<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public function index($userId)
    {
        $addresses = UserAddress::where('user_id', $userId)->get();
        return response()->json(['status' => 1, 'addresses' => $addresses]);
    }

    public function store(Request $request, $userId)
    {
        $this->validate($request, [
            'label' => 'nullable|string',
            'recipient_name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'required|string',
        ]);
        $data = $request->only(['label', 'recipient_name', 'phone', 'address', 'province', 'city', 'postal_code']);
        $data['user_id'] = $userId;
        $address = UserAddress::create($data);
        return response()->json(['status' => 1, 'message' => 'Address added', 'address' => $address]);
    }

    public function update(Request $request, $userId, $addressId)
    {
        $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);
        $address->update($request->only(['label', 'recipient_name', 'phone', 'address', 'province', 'city', 'postal_code']));
        return response()->json(['status' => 1, 'message' => 'Address updated', 'address' => $address]);
    }

    public function destroy($userId, $addressId)
    {
        $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);
        $address->delete();
        return response()->json(['status' => 1, 'message' => 'Address deleted']);
    }

    public function setDefault($userId, $addressId)
    {
        UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);
        $address->is_default = true;
        $address->save();
        return response()->json(['status' => 1, 'message' => 'Default address set', 'address' => $address]);
    }
}
