<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Habit extends Model {
 protected $fillable=['title','description','start_date','timezone','active'];
 protected function casts(): array{return ['start_date'=>'date','active'=>'boolean'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function entries(){return $this->hasMany(HabitEntry::class);}
}
