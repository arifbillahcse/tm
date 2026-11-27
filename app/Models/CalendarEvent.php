<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CalendarEvent extends Model {
 public const KINDS=['event','meeting','focus','task','break'];
 protected $fillable=['title','description','kind','starts_at','ends_at','timezone','recurrence','repeat_count','task_id'];
 protected function casts(): array {return ['starts_at'=>'datetime','ends_at'=>'datetime','repeat_count'=>'integer'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
}
