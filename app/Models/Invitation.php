<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invitation extends Model {
 protected $fillable=['workspace_id','invited_by','email','role','token_hash','expires_at','accepted_at','revoked_at'];
 protected $hidden=['token_hash'];
 protected function casts(): array {return ['expires_at'=>'datetime','accepted_at'=>'datetime','revoked_at'=>'datetime'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function valid(): bool {return !$this->accepted_at&&!$this->revoked_at&&$this->expires_at->isFuture();}
}
