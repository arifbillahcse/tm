<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Habit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Identity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DashboardTest extends TestCase {
 use RefreshDatabase;
 private function login(User $u): static {return $this->actingAs($u)->withSession(['auth_version'=>$u->auth_version])->withoutVite();}
 private function business(User $u,string $name): Workspace {$w=Workspace::create(['owner_id'=>$u->id,'name'=>$name,'type'=>'business']);$w->members()->attach($u->id,['role'=>'owner']);return $w;}
 public function test_guest_routes_are_protected(): void {foreach(['/dashboard','/search','/notifications'] as $path)$this->get($path)->assertRedirect('/login');}
 public function test_dashboard_defaults_to_personal_and_renders_real_metrics(): void {$u=User::factory()->create();$this->login($u)->get('/dashboard')->assertOk()->assertSee('Workspace access')->assertSee('My Day')->assertDontSee('Business finance');}
 public function test_switch_requires_membership_and_post(): void {$u=User::factory()->create();$other=User::factory()->create();$w=$this->business($other,'Foreign business');$this->login($u)->post('/workspaces/'.$w->id.'/switch')->assertForbidden();$w->members()->attach($u->id,['role'=>'member']);$this->post('/workspaces/'.$w->id.'/switch')->assertRedirect('/dashboard')->assertSessionHas('workspace_id',$w->id);$this->get('/dashboard')->assertOk()->assertSee('Foreign business')->assertDontSee('Business finance')->assertDontSee('Finance overview')->assertDontSee('Finance center');$this->get('/workspaces/'.$w->id.'/switch')->assertStatus(405);}
 public function test_removed_membership_falls_back_to_personal(): void {$u=User::factory()->create();$other=User::factory()->create();$w=$this->business($other,'Revoked business');$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/dashboard')->assertOk()->assertDontSee('Revoked business');}
 public function test_search_does_not_leak_private_or_foreign_workspaces(): void {$u=User::factory()->create();$other=User::factory()->create();$p=Identity::personal($other);$p->update(['name'=>'Secret Private']);$p->members()->attach($u->id,['role'=>'admin']);$this->business($other,'Secret Foreign');$this->business($u,'Secret Allowed');$this->login($u)->get('/search?q=Secret')->assertOk()->assertSee('Secret Allowed')->assertDontSee('Secret Private')->assertDontSee('Secret Foreign');}
 public function test_notification_activity_scoped_to_current_workspace_and_user(): void {$u=User::factory()->create();$other=User::factory()->create();$w=$this->business($u,'Allowed');$foreign=$this->business($other,'Foreign');Identity::audit('allowed_event',$u,$w);Identity::audit('other_actor_event',$other,$w);Identity::audit('foreign_event',$u,$foreign);$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/notifications')->assertOk()->assertSee('Allowed event')->assertDontSee('Other actor event')->assertDontSee('Foreign event');}
 public function test_private_pages_are_not_cached(): void {$u=User::factory()->create();$this->login($u)->get('/dashboard')->assertOk()->assertHeader('Referrer-Policy','no-referrer')->assertHeader('Cache-Control','no-store, private');}
 public function test_forged_personal_membership_cannot_switch_or_become_current(): void {$u=User::factory()->create();$other=User::factory()->create();$p=Identity::personal($other);$p->update(['name'=>'Hidden private space']);$p->members()->attach($u->id,['role'=>'admin']);$this->login($u)->post('/workspaces/'.$p->id.'/switch')->assertForbidden();$this->withSession(['workspace_id'=>$p->id])->get('/dashboard')->assertOk()->assertDontSee('Hidden private space');}
 public function test_workspace_names_are_escaped(): void {$u=User::factory()->create();$w=$this->business($u,'<script>alert(1)</script>');$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/dashboard')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);}
 public function test_search_and_notifications_are_not_cached(): void {$u=User::factory()->create();$this->login($u);foreach(['/search?q=Personal','/notifications'] as $url)$this->get($url)->assertOk()->assertHeader('Cache-Control','no-store, private');}
 public function test_dashboard_batches_habit_checkins_and_respects_each_habit_timezone(): void {
  $u=User::factory()->create(['timezone'=>'UTC']);$w=Identity::personal($u);
  $this->travelTo(Carbon::parse('2026-11-27 18:00:00 UTC'));
  foreach([['Asia/Dhaka','2026-11-28'],['America/Los_Angeles','2026-11-27']] as [$timezone,$date]){
   $habit=new Habit(['title'=>'Habit '.$timezone,'start_date'=>'2026-11-20','timezone'=>$timezone,'active'=>true]);$habit->workspace_id=$w->id;$habit->save();
   $habit->entries()->create(['local_date'=>$date]);
  }
  $queries=[];DB::listen(function($query)use(&$queries){if(str_contains(strtolower($query->sql),'habit_entries'))$queries[]=$query->sql;});
  $this->login($u)->get('/dashboard')->assertOk()->assertSee('2 / 2');
  $this->assertCount(1,$queries,'Dashboard should fetch all timezone-specific daily habit check-ins with one aggregate query.');
 }

}
