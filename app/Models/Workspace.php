<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Workspace extends Model {
 protected $fillable=['owner_id','name','type','personal_owner_id'];
 public function members(){return $this->belongsToMany(User::class,'workspace_memberships')->withPivot('role')->withTimestamps();}
 public function invitations(){return $this->hasMany(Invitation::class);}
 public function roleFor(User $user): ?string {
  if($this->type==='personal') return (int)$this->owner_id===(int)$user->id?'owner':null;
  if($this->type!=='business')return null;
  if((int)$this->owner_id===(int)$user->id)return 'owner';
  $role=$this->members()->where('users.id',$user->id)->first()?->pivot->role;
  return in_array($role,['admin','manager','member'],true)?$role:null;
 }
}
