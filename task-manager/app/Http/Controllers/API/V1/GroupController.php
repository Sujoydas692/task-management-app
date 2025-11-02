<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, (int) $request->get('per_page', 20));

        $query = Group::query();

        $groups = $query->latest('created_at')->paginate($perPage);

        return $this->success($groups, 'Group Data');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);

        try {
            $group = Group::create([
                'name' => $data['name'],
            ]);
            return $this->success($group, 'Group Create Success');
        } catch (\Exception $e) {
            Log::error('Group Store Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function show(Group $group): JsonResponse
    {
        try {
            return $this->success($group, 'Single Group Data');
        } catch (\Exception $e) {
            Log::error('Group Show Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);

        try {
            $group->update($data);
            return $this->success($group, 'Group Update Success');
        } catch (\Exception $exception){
            Log::error('Group Update Error : ' .$exception->getMessage());
            return $this->error();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        try {
            $group->delete();
            return $this->success('','Group Delete Success');

        } catch (\Exception $exception){
            Log::error('Group Delete Error : ' .$exception->getMessage());
            return $this->error();
        }
    }
}
