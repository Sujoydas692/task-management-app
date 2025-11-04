<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, (int) $request->get('per_page', 20));

        $tasks = Task::with(['createdBy', 'assignments.assigneeUser', 'assignments.assigneeGroup.users'])
            ->latest()
            ->paginate($perPage);

        $tasks->getCollection()->transform(function ($task) {
            $assignedUsers = collect();

            foreach ($task->assignments as $assignment) {
                if ($assignment->assignee_type === 'user' && $assignment->assigneeUser) {
                    $assignedUsers->push($assignment->assigneeUser->only(['id', 'name', 'email']));
                } elseif ($assignment->assignee_type === 'group' && $assignment->assigneeGroup) {
                    $groupUsers = $assignment->assigneeGroup->users;
                    foreach ($groupUsers as $user) {
                        $assignedUsers->push($user->only(['id', 'name', 'email']));
                    }
                }
            }

            $task->assigned_users = $assignedUsers->unique('id')->values();
            return $task;
        });


        return $this->success($tasks, 'Task Data');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $task = Task::create([
                'created_by' => $request->user()->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'created',
            ]);
            return $this->success($task, 'Task Create Success');
        } catch (\Exception $e) {
            Log::error('Task Store Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        try {
            $task->load(['createdBy', 'assignedUsers']);
            return $this->success($task, 'Single Task Data');
        } catch (\Exception $e) {
            Log::error('Task Show Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskUpdateRequest $request, Task $task)
    {
        try {
            $data = $request->validated();
            $task->fill($data)->save();

            return $this->success($task, 'Task Update Success');

        } catch (\Exception $exception){
            Log::error('Task Update Error : ' .$exception->getMessage());
            return $this->error();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        try {
            $task->delete();
            return $this->success('','Task Delete Success');

        } catch (\Exception $exception){
            Log::error('Task Delete Error : ' .$exception->getMessage());
            return $this->error();
        }
    }

//    public function updateStatus(Request $request, Task $task): JsonResponse
//    {
//        try {
//            $validated = $request->validate([
//                'status' => 'required|in:created,assigned,progress,hold,completed,cancelled',
//            ]);
//
//            $task->status = $validated['status'];
//            $task->save();
//
//            return $this->success($task, 'Task Status Updated Successfully');
//        } catch (\Exception $e) {
//            Log::error('Task Status Update Error: ' . $e->getMessage());
//            return $this->error();
//        }
//    }

    public function trashed(Request $request): JsonResponse
    {
        try {
            $perPage = min(100, (int) $request->get('per_page', 20));

            $tasks = Task::onlyTrashed()
                ->with('createdBy')
                ->latest('deleted_at')
                ->paginate($perPage);

            return $this->success($tasks, 'Trashed Task Data');
        } catch (\Exception $e) {
            Log::error('Trashed Task Fetch Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function restore(Task $task): JsonResponse
    {
        try {
            if (!$task->trashed()) {
                return $this->error('Task is not deleted', 400);
            }

            $task->restore();
            return $this->success($task, 'Task restored successfully');
        } catch (\Exception $e) {
            Log::error('Task Restore Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function forceDelete(Task $task): JsonResponse
    {
        try {
            if (!$task->trashed()) {
                return $this->error('Task is not deleted, cannot force delete', 400);
            }

            $task->forceDelete();
            return $this->success(null, 'Task permanently deleted');
        } catch (\Exception $e) {
            Log::error('Task Force Delete Error: ' . $e->getMessage());
            return $this->error();
        }
    }

}
