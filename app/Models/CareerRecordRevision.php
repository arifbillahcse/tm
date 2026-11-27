<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CareerRecordRevision extends Model {
 protected $fillable=['version','snapshot'];
 protected function casts(): array{return ['snapshot'=>'array'];}
}
