<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAssignment extends Model
{
    protected $fillable = [
        'task_id',
        'assignee_type',
        'assignee_id',
        'assigned_by',
        'assigned_at',
    ];


    protected $casts = [
        'assigned_at'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assigneeUser()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assigneeGroup()
    {
        return $this->belongsTo(Group::class, 'assignee_id');
    }
}
