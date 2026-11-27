<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GoalMilestone extends Model {
 protected $fillable=['title','due_date','completed_at'];
 protected function casts(): array{return ['due_date'=>'date','completed_at'=>'datetime'];}
}
