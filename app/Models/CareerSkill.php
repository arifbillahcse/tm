<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CareerSkill extends Model {
 protected $fillable=['name','category','current_level','target_level','evidence'];
 protected function casts(): array{return ['current_level'=>'integer','target_level'=>'integer'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function gap(): int{return max(0,$this->target_level-$this->current_level);}
}
