<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Resource;
use App\Models\Task;
use App\Models\User;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','title','description','category'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function resources() {
        return $this->hasMany(Resource::class);
    }
}
?>
