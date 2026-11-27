<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TimeEntry extends Model {
 use SoftDeletes;
 protected $fillable=['title','mode','billable','task_id','started_at','ended_at'];
 protected function casts(): array{return ['started_at'=>'datetime','ended_at'=>'datetime','billable'=>'boolean'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function revisions(){return $this->hasMany(TimeEntryRevision::class);}
 public function seconds(): int{return $this->ended_at?max(0,$this->ended_at->timestamp-$this->started_at->timestamp):0;}
}
