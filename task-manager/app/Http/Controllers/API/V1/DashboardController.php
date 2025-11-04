<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\error;

class DashboardController extends Controller
{
    public function adminData(): JsonResponse
    {
        try {
            $data = [
                'total_users' => User::where('type', '!=', 'Admin')->count(),
                'total_tasks' => Task::count(),
                'total_assignments' => TaskAssignment::count(),
                'completed_tasks' => TaskAssignment::where('status', 'completed')->count(),
                'total_groups' => Group::count(),

                'taskLabels' => ['Assigned', 'In Progress', 'On Hold', 'Completed'],
                'taskCounts' => [
                    TaskAssignment::where('status', 'assigned')->count(),
                    TaskAssignment::where('status', 'progress')->count(),
                    TaskAssignment::where('status', 'hold')->count(),
                    TaskAssignment::where('status', 'completed')->count(),
                ],
            ];

            return $this->success($data, 'Dashboard data loaded successfully');

        } catch (\Exception $e) {
            Log::error('Dashboard Data Error: ' . $e->getMessage());

            return $this->error();
        }
    }

    // 🔹 User dashboard data
    public function userData(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $groupIds = GroupUser::where('user_id', $user->id)->pluck('group_id')->toArray();

            $assignedTasks = TaskAssignment::where(function ($query) use ($user, $groupIds) {
                $query->where(function ($q) use ($user) {
                    $q->where('assignee_type', 'User')
                        ->where('assignee_id', $user->id);
                });

                if (!empty($groupIds)) {
                    $query->orWhere(function ($q) use ($groupIds) {
                        $q->where('assignee_type', 'Group')
                            ->whereIn('assignee_id', $groupIds);
                    });
                }
            });
            
            $data = [
                'assigned_tasks' => (clone $assignedTasks)->count(),

                'completed_tasks' => (clone $assignedTasks)
                    ->where('status', 'completed')
                    ->count(),

                'projectLabels' => ['In Progress', 'On Hold', 'Completed'],
                'projectCounts' => [
                    (clone $assignedTasks)->where('status', 'progress')->count(),
                    (clone $assignedTasks)->where('status', 'hold')->count(),
                    (clone $assignedTasks)->where('status', 'completed')->count(),
                ],
            ];

            return $this->success($data, 'Dashboard data loaded successfully');

        } catch (\Exception $e) {
            Log::error('Dashboard Data Error: ' . $e->getMessage());

            return $this->error();
        }
    }
}
