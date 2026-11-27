<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HabitEntry extends Model {
 protected $fillable=['local_date'];
 protected function casts(): array{return ['local_date'=>'date'];}
}
