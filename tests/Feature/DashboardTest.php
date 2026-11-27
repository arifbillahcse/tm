<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Identity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DashboardTest extends TestCase {
 use RefreshDatabase;
 private function login(User $u): static {return $this->actingAs($u)->withSession(['auth_version'=>$u->auth_version])->withoutVite();}
 private function business(User $u,string $name): Workspace {$w=Workspace::create(['owner_id'=>$u->id,'name'=>$name,'type'=>'business']);$w->members()->attach($u->id,['role'=>'owner']);return $w;}
 public function test_guest_routes_are_protected(): void {foreach(['/dashboard','/search','/notifications'] as $path)$this->get($path)->assertRedirect('/login');}
 public function test_dashboard_defaults_to_personal_and_renders_real_metrics(): void {$u=User::factory()->create();$this->login($u)->get('/dashboard')->assertOk()->assertSee('Workspace access')->assertSee('My Day')->assertDontSee('Business finance');}
 public function test_switch_requires_membership_and_post(): void {$u=User::factory()->create();$other=User::factory()->create();$w=$this->business($other,'Foreign business');$this->login($u)->post('/workspaces/'.$w->id.'/switch')->assertForbidden();$w->members()->attach($u->id,['role'=>'member']);$this->post('/workspaces/'.$w->id.'/switch')->assertRedirect('/dashboard')->assertSessionHas('workspace_id',$w->id);$this->get('/dashboard')->assertOk()->assertSee('Foreign business')->assertSee('Business finance');$this->get('/workspaces/'.$w->id.'/switch')->assertStatus(405);}
 public function test_removed_membership_falls_back_to_personal(): void {$u=User::factory()->create();$other=User::factory()->create();$w=$this->business($other,'Revoked business');$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/dashboard')->assertOk()->assertDontSee('Revoked business');}
 public function test_search_does_not_leak_private_or_foreign_workspaces(): void {$u=User::factory()->create();$other=User::factory()->create();$p=Identity::personal($other);$p->update(['name'=>'Secret Private']);$p->members()->attach($u->id,['role'=>'admin']);$this->business($other,'Secret Foreign');$this->business($u,'Secret Allowed');$this->login($u)->get('/search?q=Secret')->assertOk()->assertSee('Secret Allowed')->assertDontSee('Secret Private')->assertDontSee('Secret Foreign');}
 public function test_notification_activity_scoped_to_current_workspace_and_user(): void {$u=User::factory()->create();$other=User::factory()->create();$w=$this->business($u,'Allowed');$foreign=$this->business($other,'Foreign');Identity::audit('allowed_event',$u,$w);Identity::audit('other_actor_event',$other,$w);Identity::audit('foreign_event',$u,$foreign);$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/notifications')->assertOk()->assertSee('Allowed event')->assertDontSee('Other actor event')->assertDontSee('Foreign event');}
 public function test_private_pages_are_not_cached(): void {$u=User::factory()->create();$this->login($u)->get('/dashboard')->assertOk()->assertHeader('Referrer-Policy','no-referrer')->assertHeader('Cache-Control','no-store, private');}
}
