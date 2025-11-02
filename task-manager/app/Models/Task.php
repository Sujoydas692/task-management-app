<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'created',
        'assigned',
        'progress',
        'hold',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'status'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUsers()
    {
        return $this->hasMany(TaskAssignment::class, 'task_id')
            ->where('assignee_type', 'user')
            ->with('assigneeUser:id,name,email');
    }

    public function assignedGroups()
    {
        return $this->hasMany(TaskAssignment::class, 'task_id')
            ->where('assignee_type', 'group')
            ->with('assigneeGroup:id,name');
    }


    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class, 'task_id');
    }

}
