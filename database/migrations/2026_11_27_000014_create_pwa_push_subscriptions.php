<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void { Schema::create('pwa_push_subscriptions',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('workspace_id')->constrained()->cascadeOnDelete();$t->char('endpoint_hash',64);$t->longText('endpoint_encrypted');$t->text('public_key_encrypted');$t->text('auth_token_encrypted');$t->string('content_encoding',32)->default('aes128gcm');$t->timestamps();$t->unique(['user_id','workspace_id','endpoint_hash'],'pwa_push_user_workspace_endpoint_unique');$t->index(['workspace_id','user_id']);}); }
 public function down():void { Schema::dropIfExists('pwa_push_subscriptions'); }
};