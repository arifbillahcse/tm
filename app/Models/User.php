<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
 use HasFactory, Notifiable;
 protected $attributes=['auth_version'=>0];
 protected $fillable=['name','email','password','timezone'];
 protected $hidden=['password','remember_token','two_factor_secret','recovery_codes','totp_last_step','auth_version'];
 protected function casts(): array {return ['email_verified_at'=>'datetime','password'=>'hashed','two_factor_secret'=>'encrypted','recovery_codes'=>'encrypted:array','auth_version'=>'integer','totp_last_step'=>'integer'];}
 public function workspaces(){return $this->belongsToMany(Workspace::class,'workspace_memberships')->withPivot('role')->withTimestamps();}
 public function sendPasswordResetNotification($token): void { $this->notify(new \App\Notifications\PasswordReset($token)); }
}
