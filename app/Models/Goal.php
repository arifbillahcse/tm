<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Goal extends Model {
 public const HORIZONS=['lifetime','year','quarter','month'];
 public const CATEGORIES=['personal','career','health','learning','finance','business','other'];
 protected $fillable=['title','description','horizon','category','due_date','target','actual','unit','status','vision'];
 protected function casts(): array{return ['due_date'=>'date','target'=>'decimal:2','actual'=>'decimal:2'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function milestones(){return $this->hasMany(GoalMilestone::class);}
 public function tasks(){return $this->belongsToMany(Task::class,'goal_tasks');}
 public function progress(): int{return (int)min(100,max(0,round(100*(float)$this->actual/max(.01,(float)$this->target))));}
}
