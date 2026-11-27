<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class CrmAccess {
 public const LEAD_STAGES=['new','contacted','qualified','converted','lost'];
 public const DEAL_STAGES=['qualified','proposal','negotiation','won','lost'];
 public static function role(Workspace $w,User $u):string{$role=TeamAccess::role($w,$u);abort_unless(TeamAccess::staff($role),403);return $role;}
 public static function canView(Workspace $w,User $u):bool{return $w->type==='business'&&TeamAccess::staff($w->roleFor($u)??'');}
 public static function lock(Workspace $w,User $u):string{$role=TeamAccess::lock($w,$u);abort_unless(TeamAccess::staff($role),403);return $role;}
 public static function query(Workspace $w,string $table){return DB::table($table)->where('workspace_id',$w->id);}
 public static function stats(Workspace $w,User $u):array{
  self::role($w,$u);$leads=self::query($w,'crm_leads');$deals=self::query($w,'crm_deals');$today=now()->setTimezone($u->timezone?:'UTC')->toDateString();
  $totals=(clone $deals)->selectRaw('currency, stage, COUNT(*) as count, SUM(amount) as total')->groupBy('currency','stage')->get();
  return ['contacts'=>self::query($w,'crm_contacts')->count(),'leads'=>(clone $leads)->whereIn('status',['new','contacted','qualified'])->count(),'won'=>(clone $deals)->where('stage','won')->count(),'due'=>self::query($w,'crm_followups')->whereNull('completed_at')->where('due_date','<=',$today)->count(),'totals'=>$totals,'sources'=>(clone $leads)->selectRaw('source, COUNT(*) as count')->groupBy('source')->orderByDesc('count')->limit(20)->get()];
 }
 public static function audit(Workspace $w,User $u,string $event,string $type,int $id):void{
  DB::table('crm_activity')->insert(['workspace_id'=>$w->id,'user_id'=>$u->id,'event'=>$event,'resource_type'=>$type,'resource_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);Identity::audit($event,$u,$w);
 }
}
