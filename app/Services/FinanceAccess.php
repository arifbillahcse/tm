<?php
namespace App\Services;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class FinanceAccess {
 public static function canView(Workspace $w,User $u):bool{return ($w->type==='personal'&&(int)$w->owner_id===(int)$u->id)||($w->type==='business'&&in_array($w->roleFor($u),['owner','admin'],true));}
 public static function authorize(Workspace $w,User $u):void{abort_unless(self::canView($w,$u),403);}
 public static function lock(Workspace $w,User $u):Workspace{$w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();self::authorize($w,$u);return $w;}
 public static function query(Workspace $w,string $table){return DB::table('finance_'.$table)->where('workspace_id',$w->id);}
 public static function row(Workspace $w,string $table,int $id){$row=self::query($w,$table)->where('id',$id)->first();abort_unless($row,404);return $row;}
 public static function minor(mixed $value):int{
  $s=(string)$value;if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$s))throw ValidationException::withMessages(['amount'=>'Use a nonnegative amount with up to ten whole digits and two decimal places.']);
  [$whole,$fraction]=array_pad(explode('.',$s,2),2,'');return (int)$whole*100+(int)str_pad($fraction,2,'0');
 }
 public static function money(int|string $minor):string{$s=(string)$minor;$negative=str_starts_with($s,'-');$s=str_pad(ltrim($s,'-'),3,'0',STR_PAD_LEFT);return ($negative?'-':'').substr($s,0,-2).'.'.substr($s,-2);}
 public static function insert(Workspace $w,string $table,array $data):int{return DB::table('finance_'.$table)->insertGetId($data+['workspace_id'=>$w->id,'created_at'=>now(),'updated_at'=>now()]);}
 public static function post(Workspace $w,User $u,array $data):int{
  $a=self::row($w,'accounts',$data['account_id']);$c=self::row($w,'categories',$data['category_id']);
  abort_unless($c->kind===$data['kind']||!empty($data['reversal_of']),422,'Category must match direction.');
  abort_unless($data['amount_minor']>0,422,'Amount must be positive.');
  return self::insert($w,'transactions',$data+['currency'=>$a->currency,'user_id'=>$u->id]);
 }
 public static function stats(Workspace $w,User $u,string $month):array{
  self::authorize($w,$u);$q=self::query($w,'transactions')->whereBetween('local_date',[$month.'-01',$month.'-31']);
  return (clone $q)->selectRaw("currency, SUM(CASE WHEN kind='income' AND reversal_of IS NULL THEN amount_minor WHEN kind='expense' AND reversal_of IS NOT NULL THEN -amount_minor ELSE 0 END) AS income, SUM(CASE WHEN kind='expense' AND reversal_of IS NULL THEN amount_minor WHEN kind='income' AND reversal_of IS NOT NULL THEN -amount_minor ELSE 0 END) AS expense")->groupBy('currency')->get()->map(fn($r)=>['currency'=>$r->currency,'income'=>self::money($r->income),'expense'=>self::money($r->expense),'net'=>self::money((int)$r->income-(int)$r->expense)])->all();
 }
 public static function tick():void{
  foreach(DB::table('finance_recurring')->where('active',true)->where('next_date','<=',now()->addDay()->toDateString())->orderBy('id')->cursor() as $candidate){
   DB::transaction(function()use($candidate){$w=Workspace::whereKey($candidate->workspace_id)->lockForUpdate()->first();$u=User::find($candidate->user_id);$r=self::row($w,'recurring',$candidate->id);if(!$r->active)return;if(!$u||!self::canView($w,$u)){self::query($w,'recurring')->where('id',$r->id)->update(['active'=>false,'updated_at'=>now()]);return;}
    $today=now()->setTimezone($r->timezone)->toDateString();$date=\Illuminate\Support\Carbon::parse($r->next_date,'UTC');
    for($i=0;$i<30&&$date->toDateString()<=$today;$i++){$day=$date->toDateString();if(!self::query($w,'transactions')->where('recurring_id',$r->id)->where('local_date',$day)->exists()){self::post($w,$u,['account_id'=>$r->account_id,'category_id'=>$r->category_id,'kind'=>$r->kind,'title'=>$r->title,'amount_minor'=>$r->amount_minor,'local_date'=>$day,'recurring_id'=>$r->id]);Identity::audit('finance_recurring_posted',$u,$w);}
     if($r->frequency==='monthly'){$date->startOfMonth()->addMonth()->day(min($r->anchor_day,$date->daysInMonth));}elseif($r->frequency==='weekly')$date->addWeek();else $date->addDay();
    }self::query($w,'recurring')->where('id',$r->id)->update(['next_date'=>$date->toDateString(),'updated_at'=>now()]);
   });
  }
 }
}
