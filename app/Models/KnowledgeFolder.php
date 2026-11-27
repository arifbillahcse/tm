<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class KnowledgeFolder extends Model {
 protected $fillable=['name'];
 public function workspace(){return $this->belongsTo(Workspace::class);}
}
