<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Task extends Model {
 use SoftDeletes;
 public const STATUSES=['pending','in_progress','completed'];
 public const PRIORITIES=['low','medium','high','urgent'];
 protected $fillable=['title','description','status','priority','due_at','planned_date','recurrence','recurrence_timezone','remind_at','tags','parent_id'];
 protected function casts(): array {return ['due_at'=>'datetime','planned_date'=>'date','completed_at'=>'datetime','occurrence_at'=>'datetime','remind_at'=>'datetime','reminded_at'=>'datetime','tags'=>'array'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function children(){return $this->hasMany(self::class,'parent_id');}
 public function dependencies(){return $this->belongsToMany(self::class,'task_dependencies','task_id','depends_on_id');}
 public function attachments(){return $this->hasMany(TaskAttachment::class);}
 public function history(){return $this->hasMany(TaskHistory::class);}
}
