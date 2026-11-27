<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CareerLearningSession extends Model {
 protected $fillable=['career_record_id','local_date','minutes','topic','reflection'];
 protected function casts(): array{return ['local_date'=>'date','minutes'=>'integer'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
}
