<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NoteAttachment extends Model {
 protected $fillable=['path','name','size'];
 protected $hidden=['path'];
}
