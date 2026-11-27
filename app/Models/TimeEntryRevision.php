<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TimeEntryRevision extends Model {
 protected $fillable=['user_id','before','reason'];
 protected function casts(): array{return ['before'=>'array'];}
}
