<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SecurityBaselineTest extends TestCase {
 use RefreshDatabase;
 public function test_web_responses_include_baseline_security_headers():void {
  $r=$this->get('/login')->assertOk();
  $r->assertHeader('X-Content-Type-Options','nosniff')
    ->assertHeader('X-Frame-Options','DENY')
    ->assertHeader('Referrer-Policy','no-referrer')
    ->assertHeader('Permissions-Policy','camera=(), microphone=(), geolocation=(), payment=()')
    ->assertHeader('Cross-Origin-Opener-Policy','same-origin')
    ->assertHeader('Cross-Origin-Resource-Policy','same-origin');
 }
 public function test_security_headers_do_not_enable_hsts_for_plain_http():void {
  $this->get('/login')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
 }
 public function test_private_web_responses_are_not_cacheable_and_keep_no_referrer():void {
  $this->get('/login')->assertOk()
   ->assertHeader('Cache-Control','no-store, private')
   ->assertHeader('Referrer-Policy','no-referrer');
 }
 public function test_hsts_is_added_only_for_secure_requests():void {
  $this->withServerVariables(['HTTPS'=>'on'])->get('/login')->assertOk()
   ->assertHeader('Strict-Transport-Security','max-age=31536000; includeSubDomains');
 }
 public function test_shell_has_skip_navigation_and_accessible_main_landmark():void {
  $shell=file_get_contents(resource_path('views/layouts/shell.blade.php'));
  $css=file_get_contents(resource_path('css/app.css'));
  $this->assertStringContainsString('class="skip" href="#main-content"',$shell);
  $this->assertStringContainsString('<main id="main-content"',$shell);
  $this->assertStringContainsString('aria-label="Main navigation"',$shell);
  $this->assertStringContainsString(':focus-visible',$css);
  $this->assertStringContainsString('prefers-reduced-motion:reduce',$css);
 }

}