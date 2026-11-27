<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Note;
use App\Models\Task;
use App\Services\Identity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class IntelligenceTest extends TestCase {
 use RefreshDatabase;
 private function login(User $u):static{return $this->actingAs($u)->withSession(['auth_version'=>$u->auth_version])->withoutVite();}
 public function test_guest_cannot_access_intelligence():void{$this->get('/intelligence')->assertRedirect('/login');}
 public function test_personal_analytics_and_daily_plan_are_scoped():void{$u=User::factory()->create();$w=Identity::personal($u);Task::create(['workspace_id'=>$w->id,'title'=>'My task','status'=>'pending','priority'=>'urgent']);$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/intelligence')->assertOk()->assertSee('My task');$this->get('/intelligence/plan')->assertOk()->assertJsonPath('data.tasks.0.title','My task');}
 public function test_cannot_read_other_persons_notes_through_workspace_or_ai():void{$u=User::factory()->create();$other=User::factory()->create();$foreign=Identity::personal($other);Note::create(['workspace_id'=>$foreign->id,'title'=>'Private foreign note','kind'=>'note','body'=>'confidential','tags'=>[],'archived'=>false,'version'=>1]);$this->login($u)->withSession(['workspace_id'=>$foreign->id])->get('/intelligence')->assertForbidden();$this->postJson('/intelligence/ask',['question'=>'What is confidential?'])->assertStatus(403);}
 public function test_business_member_analytics_are_limited_to_available_modules():void{$owner=User::factory()->create();$member=User::factory()->create();$w=Workspace::create(['owner_id'=>$owner->id,'name'=>'Business','type'=>'business']);$w->members()->attach($member->id,['role'=>'member']);$this->login($member)->withSession(['workspace_id'=>$w->id])->get('/intelligence/report')->assertOk()->assertJsonStructure(['data'=>['team','projects']])->assertJsonMissingPath('data.finance_monthly');}
 public function test_ask_rejects_unbounded_or_short_questions():void{$u=User::factory()->create();$this->login($u)->postJson('/intelligence/ask',['question'=>'x'])->assertUnprocessable();$this->postJson('/intelligence/ask',['question'=>str_repeat('x',2001)])->assertUnprocessable();}
 public function test_local_ai_disabled_does_not_break_deterministic_analytics():void{config(['intelligence.enabled'=>false]);$u=User::factory()->create();$w=Identity::personal($u);$this->login($u)->withSession(['workspace_id'=>$w->id])->get('/intelligence/report')->assertOk();$this->postJson('/intelligence/ask',['question'=>'Summarize my notes'])->assertUnprocessable();}
 public function test_note_context_is_redacted_before_model_inference():void{$u=User::factory()->create();$w=Identity::personal($u);Note::create(['workspace_id'=>$w->id,'title'=>'Project planning','kind'=>'note','body'=>'Contact alice@example.com password=topsecret','tags'=>[],'archived'=>false,'version'=>1]);$this->assertDatabaseHas('notes',['workspace_id'=>$w->id]);$this->assertStringNotContainsString('alice@example.com',app(\App\Http\Controllers\IntelligenceController::class)->redact('alice@example.com'));}
}
