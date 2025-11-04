<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\TaskAssignStoreRequest;
use App\Models\Group;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskAssignController extends Controller
{
    public function list(Request $request, Task $task)
    {
        $perPage = min(100, (int) $request->get('per_page', 20));
        $assignments = TaskAssignment::with([
            'task:id,title,status',
            'assignedBy:id,name',
            'assigneeUser:id,name,email',
            'assigneeGroup:id,name'
        ])
            ->where('task_id', $task->id)
            ->latest()
            ->paginate($perPage);

        $assignments->getCollection()->transform(function ($a) {
            return [
                'id' => $a->id,
                'task_id' => $a->task_id,
                'assignee_type' => $a->assignee_type,
                'assignee_name' => $a->assigneeUser->name ?? $a->assigneeGroup->name ?? 'N/A',
                'assigned_by' => $a->assignedBy,
                'assigned_at' => $a->assigned_at,
                'status' => $a->status ?? 'created',
            ];
        });

        return $this->success($assignments, 'Task assignments with status');
    }

    public function store(TaskAssignStoreRequest $request, Task $task)
    {
        $validated = $request->validated();

        try {

            if($validated['assignee_type'] == 'user')
            {
                User::findOrFail($validated['assignee_id']);
            }else{
                Group::findOrFail($validated['assignee_id']);
            }

            TaskAssignment::where('task_id', $task->id)
                ->where('assignee_type', $validated['assignee_type'])
                ->where('assignee_id', $validated['assignee_id'])
                ->exists();

            $taskAssignment = TaskAssignment::create([
                'task_id' => $task->id,
                'assignee_type' => $validated['assignee_type'],
                'assignee_id' => $validated['assignee_id'],
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'status' => 'created'
            ]);

            return $this->success($taskAssignment->load(['assigneeUser:id,name', 'assigneeGroup:id,name']), 'Assignment stored successfully');


        } catch (\Exception $exception){
            Log::error('Task Assignment Error : ' .$exception->getMessage());
            return $this->error();
        }
    }

    public function destroy(Task $task, TaskAssignment $assignment)
    {
        try {
            if ($assignment->task_id !== $task->id) {
                return $this->error('This assignment does not belong to the given task.', 400);
            }

            $assignment->delete();

            return $this->success('', 'Task assignment deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Task Assignment Delete Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function updateTaskStatus(Request $request, $taskId, $assignmentId): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->type === 'Admin';

        if ($isAdmin) {
            $request->validate([
                'status' => 'required|in:created,assigned,progress,hold,completed,cancelled',
            ]);
        } else {
            $request->validate([
                'status' => 'required|in:progress,hold,completed',
            ]);
        }

        $assignment = TaskAssignment::where('task_id', $taskId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found for this task',
            ]);
        }

        if ($isAdmin) {
            $assignment->update(['status' => $request->status, 'updated_at' => now(),]);

            return response()->json([
                'success' => true,
                'message' => 'Admin updated assignment & task status successfully',
                'data' => $assignment->load('task'),
            ]);
        } else {
            $isAssignedToUser = (
                ($assignment->assignee_type === 'user' && $assignment->assignee_id === $user->id)
                ||
                ($assignment->assignee_type === 'group' && $assignment->assigneeGroup?->users->contains($user->id))
            );

            if (!$isAssignedToUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to update this task status',
                ]);
            }

            $assignment->update(['status' => $request->status, 'updated_at' => now(),]);

            return response()->json([
                'success' => true,
                'message' => 'User updated assignment status successfully',
                'data' => $assignment->fresh(),
            ]);
        }
    }





}
