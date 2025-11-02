<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        try {
            // return $this->success(new UserResource($request->user()), 'User Profile');
            $user = $request->user();
            $cacheKey = "user_profile_{$user->id}";
            $cached = Cache::remember($cacheKey, now()->addDay(), function () use ($user) {
                return new UserResource($user);
            });
            return $this->success($cached, 'User Profile');
        } catch (\Exception $e) {
            Log::error('Profile Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            if ($request->hasFile('profile_image')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profile_images', 'public');
            $data['profile_image'] = $path;
        }
            $user->update($data);
            $cacheKey = "user_profile_{$user->id}";
            Cache::put($cacheKey, new UserResource($user), now()->addDay());
            return $this->success(new UserResource($user), 'User Profile Updated');
        } catch (\Exception $e) {
            Log::error('Profile Update Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function index(): JsonResponse
    {
        try {
            $users = User::select('id', 'name', 'email', 'type')
                ->with(['groups:id'])
                ->get()
                ->map(function ($user) {
                    $user->group_id = $user->groups->first()->id ?? null;
                    unset($user->groups);
                    return $user;
                });
            return $this->success($users, 'All Users');
        } catch (\Exception $e) {
            Log::error('User List Error: ' . $e->getMessage());
            return $this->error();
        }
    }
}
