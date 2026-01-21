<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Course;
use App\Models\User;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = ['course_id','title','file_url','uploaded_by'];

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
?>
