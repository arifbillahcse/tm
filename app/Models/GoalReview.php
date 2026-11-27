<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GoalReview extends Model {
 protected $fillable=['period','period_start','reflection'];
 protected function casts(): array{return ['period_start'=>'date'];}
}
