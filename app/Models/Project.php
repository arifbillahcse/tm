<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Project extends Model {
 protected $fillable=['name','description','status','start_date','due_date','currency','budget'];
 protected function casts():array{return ['start_date'=>'date','due_date'=>'date','budget'=>'decimal:2'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function members(){return $this->belongsToMany(User::class,'project_members')->withPivot('role')->withTimestamps();}
}
