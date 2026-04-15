<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskReminder extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'reminder_time', 'status', 'time_slot', 'frequency'];
    protected $casts = [
        'reminder_time' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
?>
