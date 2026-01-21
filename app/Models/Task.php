<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Course;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['course_id','title','description','deadline','status'];

    public function course() {
        return $this->belongsTo(Course::class);
    }
}
?>