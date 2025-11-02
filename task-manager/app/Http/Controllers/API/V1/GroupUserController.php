<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\GroupUserStoreRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GroupUserController extends Controller
{
    public function list(Request $request, Group $group)
    {

        $perPage = min(100, (int) $request->get('per_page', 20));
        $groupUsers = $group->users()
            ->paginate($perPage);

        return $this->success($groupUsers, 'Group users');
    }


    public function store(GroupUserStoreRequest $request, Group $group)
    {
//        $all = $request->all();
//        print '<pre>';
//        print_r($all);
//        print '</pre>';
//        exit;
        $validated = $request->validated();

        try {
            $ids = collect($validated['user_ids'] ?? []);
            if(!empty($validated['user_id'])){
                $ids->push($validated['user_id']);
            }

            $ids = $ids->unique()->values();

            $existingUserIds = \DB::table('group_user')
                ->whereIn('user_id', $ids)
                ->pluck('user_id')
                ->toArray();

            if (!empty($existingUserIds)) {
                $existingUserNames = User::whereIn('id', $existingUserIds)
                    ->pluck('name')
                    ->implode(', ');

                return $this->error("The following users are already in another group: {$existingUserNames}", 400);
            }

            $group->users()->syncWithoutDetaching($ids);

            $groupUserData = $group->users()->select('users.id', 'users.name', 'users.email')->get();
            return $this->success($groupUserData, 'Group User Create Success');

        } catch (\Exception $exception){
            Log::error('Group User Delete Error : ' .$exception->getMessage());
            return $this->error();
        }
    }

    public function destroy(Group $group, User $user)
    {
        try {
            $group->users()->detach($user->id);
            return $this->success('', 'Group user deleted');
        } catch (\Exception $exception){
            Log::error('Group User Delete Error : ' .$exception->getMessage());
            return $this->error();
        }

    }
}
