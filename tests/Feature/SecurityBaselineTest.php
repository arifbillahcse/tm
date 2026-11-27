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
    ->assertHeader('Referrer-Policy','strict-origin-when-cross-origin')
    ->assertHeader('Permissions-Policy','camera=(), microphone=(), geolocation=(), payment=()')
    ->assertHeader('Cross-Origin-Opener-Policy','same-origin')
    ->assertHeader('Cross-Origin-Resource-Policy','same-origin');
 }
 public function test_security_headers_do_not_enable_hsts_for_plain_http():void {
  $this->get('/login')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
 }
}