<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'fullname' => 'required|string',
            'email' => 'required|email|unique:users_elsid',
            'password' => 'required|min:6',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $userData = $request->only(['fullname', 'email', 'phone', 'address']);
        $userData['password'] = Hash::make($request->password);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move('uploads/avatars', $fileName);
            $userData['avatar'] = 'uploads/avatars/' . $fileName;
        }

        $user = User::create($userData);
        return response()->json(['status' => 1, 'message' => 'Registration successful']);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 0, 'message' => 'Invalid email or password']);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'province' => $user->province,
                'postal_code' => $user->postal_code,
                'avatar' => $user->avatar
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'User not found']);
        }

        $this->validate($request, [
            'fullname' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ]);

        $updateData = [];
        foreach (['fullname', 'phone', 'address', 'city', 'province', 'postal_code'] as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        $user->update($updateData);

        // Refresh user data
        $user = User::find($id);
        return response()->json([
            'status' => 1,
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'province' => $user->province,
                'postal_code' => $user->postal_code,
                'avatar' => $user->avatar
            ]
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $this->validate($request, [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
            'user_id' => 'required|exists:users_elsid,id'
        ]);

        // Use user_id from request instead of URL parameter for mobile apps
        $user = User::find($request->user_id);

        try {
            $uploadPath = 'uploads/avatars';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Delete old avatar if exists
            if ($user->avatar && file_exists($user->avatar)) {
                unlink($user->avatar);
            }

            $file = $request->file('avatar');
            $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->move($uploadPath, $fileName);
            $avatarPath = $uploadPath . '/' . $fileName;

            $user->update(['avatar' => $avatarPath]);

            return response()->json([
                'status' => 1,
                'message' => 'Avatar updated successfully',
                'user' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'city' => $user->city,
                    'province' => $user->province,
                    'postal_code' => $user->postal_code,
                    'avatar' => $avatarPath
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to update avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'User not found']);
        }

        if ($user->avatar && file_exists($user->avatar)) {
            unlink($user->avatar);
        }

        $user->delete();
        return response()->json(['status' => 1, 'message' => 'User deleted successfully']);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'User not found']);
        }

        return response()->json(['status' => 1, 'user' => $user]);
    }

    public function index()
    {
        $users = User::all();
        return response()->json(['status' => 1, 'users' => $users]);
    }

    public function getProfile(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id'
        ]);

        $user = User::find($request->user_id);

        return response()->json([
            'status' => 1,
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'province' => $user->province,
                'postal_code' => $user->postal_code,
                'avatar' => $user->avatar
            ]
        ]);
    }

    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id',
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password'
        ]);

        try {
            $user = User::find($request->user_id);

            // Verifikasi password lama
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Password lama tidak sesuai'
                ], 400);
            }

            // Update password baru
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => 1,
                'message' => 'Password berhasil diubah'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Gagal mengubah password: ' . $e->getMessage()
            ], 500);
        }
    }
}
