<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $t){$t->text('two_factor_secret')->nullable();$t->text('recovery_codes')->nullable();$t->unsignedBigInteger('totp_last_step')->nullable();$t->unsignedInteger('auth_version')->default(0);});
  Schema::create('workspaces',function(Blueprint $t){$t->id();$t->foreignId('owner_id')->constrained('users')->restrictOnDelete();$t->string('name');$t->string('type',16);$t->foreignId('personal_owner_id')->nullable()->unique()->constrained('users')->restrictOnDelete();$t->timestamps();});
  Schema::create('workspace_memberships',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('role',16);$t->unique(['workspace_id','user_id']);$t->timestamps();});
  Schema::create('invitations',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->cascadeOnDelete();$t->foreignId('invited_by')->constrained('users')->cascadeOnDelete();$t->string('email');$t->string('role',16);$t->char('token_hash',64)->unique();$t->timestamp('expires_at');$t->timestamp('accepted_at')->nullable();$t->timestamp('revoked_at')->nullable();$t->timestamps();});
  Schema::create('audit_events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();$t->string('event',80);$t->timestamps();$t->index(['workspace_id','created_at']);});
 }
 public function down(): void {foreach(['audit_events','invitations','workspace_memberships','workspaces'] as $table) Schema::dropIfExists($table);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['two_factor_secret','recovery_codes','totp_last_step','auth_version']));}
};
