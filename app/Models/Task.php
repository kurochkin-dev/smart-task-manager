<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'estimated_hours',
        'actual_hours',
        'required_skills',
        'complexity',
        'due_date',
        'project_id',
        'assigned_user_id',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'required_skills' => 'array',
        'estimated_hours' => 'integer',
        'actual_hours' => 'integer',
        'complexity' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskLogs()
    {
        return $this->hasMany(TaskLog::class);
    }
}
