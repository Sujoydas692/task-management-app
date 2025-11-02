<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\TaskAssignStoreRequest;
use App\Models\Group;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskAssignController extends Controller
{
    public function list(Request $request, Task $task)
    {
        $perPage = min(100, (int) $request->get('per_page', 20));
        $assignments = TaskAssignment::where('task_id', $task->id)
            ->with([
                'assignedBy:id,name,email',
                'assigneeGroup:id,name',
                'assigneeUser:id,name',
            ])
            ->latest()
            ->paginate($perPage);

        // Transform each assignment to include readable assignee name
        $assignments->getCollection()->transform(function ($assignment) {
            if ($assignment->assignee_type === 'group') {
                $assignment->assignee_name = optional($assignment->assigneeGroup)->name;
            } elseif ($assignment->assignee_type === 'user') {
                $assignment->assignee_name = optional($assignment->assigneeUser)->name;
            } else {
                $assignment->assignee_name = 'N/A';
            }
            return $assignment;
        });

        return $this->success($assignments, 'Group users');
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

            $exists = TaskAssignment::where('task_id', $task->id)
                ->where('assignee_type', $validated['assignee_type'])
                ->where('assignee_id', $validated['assignee_id'])
                ->exists();

            if ($exists) {
                return $this->error('Already assigned to this user or group.', 400);
            }


            $taskAssignment = TaskAssignment::create([
                'task_id' => $task->id,
                'assignee_type' => $validated['assignee_type'],
                'assignee_id' => $validated['assignee_id'],
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
            ]);

            return $this->success($taskAssignment, 'Store Successful');


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

}
