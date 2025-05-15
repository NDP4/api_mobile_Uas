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
                'fullname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address
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

        $user->update($request->all());
        return response()->json(['status' => 1, 'message' => 'User updated successfully']);
    }

    public function updateAvatar(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'User not found']);
        }

        $this->validate($request, [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048' // max 2MB
        ]);

        if ($request->hasFile('avatar')) {
            // Create uploads directory if it doesn't exist
            $uploadPath = 'uploads/avatars';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Delete old avatar if exists
            if ($user->avatar && file_exists($user->avatar)) {
                unlink($user->avatar);
            }

            $file = $request->file('avatar');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move($uploadPath, $fileName);
            $avatarPath = $uploadPath . '/' . $fileName;

            $user->update(['avatar' => $avatarPath]);
            return response()->json([
                'status' => 1,
                'message' => 'Avatar updated successfully',
                'avatar' => $avatarPath
            ]);
        }

        return response()->json(['status' => 0, 'message' => 'No avatar file uploaded']);
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
}
