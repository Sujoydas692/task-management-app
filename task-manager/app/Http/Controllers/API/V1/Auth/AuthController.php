<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\LoginRequest;
use App\Http\Requests\API\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['type'] = $data['type'] ?? 'user';

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $data['profile_image'] = $path;
            }

            $user = User::create($data);
            return $this->success(new UserResource($user), 'Registration successful');
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::whereEmail($request->email)->first();
            if(!Hash::check($request->password, $user->password)){
                return $this->error(['Invalid credentials'], 422);
            }

            $authToken = $user->createToken('authToken')->plainTextToken;
            $data = [
                'user' => new UserResource($user),
                'token' => $authToken
            ];
            return $this->success($data, 'Login successful');
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Cache::forget("user_profile_{$user->id}");
            $user->tokens()->delete();
            return $this->success(null, 'Logout successful');
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            return $this->error();
        }
    }
}
