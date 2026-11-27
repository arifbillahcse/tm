<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FocusSession extends Model {
 public const MODES=['pomodoro','deep','custom','short_break','long_break'];
 protected $fillable=['title','mode','target_seconds','billable','task_id'];
 protected function casts(): array{return ['started_at'=>'datetime','finished_at'=>'datetime','billable'=>'boolean','target_seconds'=>'integer','version'=>'integer'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function entries(){return $this->hasMany(TimeEntry::class);}
}
