<?php
namespace App\Services;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
class Identity {
 public static function personal(User $user): Workspace {
  $workspace=Workspace::firstOrCreate(['personal_owner_id'=>$user->id],['owner_id'=>$user->id,'name'=>'Personal','type'=>'personal']);
  $workspace->members()->syncWithoutDetaching([$user->id=>['role'=>'owner']]);return $workspace;
 }
 public static function audit(string $event,?User $user=null,?Workspace $workspace=null): void {
  DB::table('audit_events')->insert(['event'=>$event,'user_id'=>$user?->id,'workspace_id'=>$workspace?->id,'created_at'=>now(),'updated_at'=>now()]);
 }
}
