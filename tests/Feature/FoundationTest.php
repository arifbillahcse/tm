<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class FoundationTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_page_renders(): void {
        $this->withoutVite()->get('/')->assertOk()->assertSee('Softorio Workspace');
    }
    public function test_health_endpoint(): void {
        $this->get('/up')->assertOk();
    }
    public function test_foundation_tables_exist(): void {
        foreach (['users','password_reset_tokens','sessions','cache','cache_locks','jobs','job_batches','failed_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $user = User::factory()->create();
        $this->assertSame('Asia/Dhaka', $user->timezone);
    }
    public function test_public_registration_is_unavailable(): void {
        $this->get('/register')->assertNotFound();
    }
}
