<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CareerRecord extends Model {
 protected $fillable=['kind','title','status','description','details','target_date'];
 protected function casts(): array{return ['details'=>'array','target_date'=>'date','version'=>'integer'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function revisions(){return $this->hasMany(CareerRecordRevision::class);}
}
