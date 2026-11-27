<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlanningTemplate extends Model {
 protected $fillable=['name','blocks'];
 protected function casts(): array {return ['blocks'=>'array'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
}
