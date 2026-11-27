<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaskAttachment extends Model {
 protected $fillable=['path','name','size'];
 protected $hidden=['path'];
}
