<?php
use App\Models\User;
use App\Services\Identity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
Artisan::command('workspace:bootstrap-owner {email}',function(){
 $email=strtolower($this->argument('email'));$name=$this->ask('Owner name');$password=$this->secret('Password (at least 12 characters)');$confirmation=$this->secret('Confirm password');
 $v=Validator::make(['email'=>$email,'name'=>$name,'password'=>$password,'password_confirmation'=>$confirmation],['email'=>'required|email|max:255','name'=>'required|string|max:255','password'=>['required','confirmed','max:72',function ($attribute,$value,$fail) { if (is_string($value) && (strlen($value)>72 || str_contains($value, chr(0)))) $fail('The password must be at most 72 bytes and contain no null bytes.'); },Password::min(12)]]);
 if($v->fails()){$this->error('Invalid owner details.');return 1;}
 if(User::exists()){$this->error('Bootstrap is available only before any account exists.');return 1;}
 DB::transaction(function()use($email,$name,$password){$user=User::create(compact('email','name','password'));Identity::personal($user);Identity::audit('owner_bootstrapped',$user);});$this->info('Owner created. Sign in and enroll 2FA.');return 0;
})->purpose('Create the initial account from a trusted server terminal');

Artisan::command('tasks:tick',function(){app(\App\Services\TaskService::class)->tick();$this->info('Task scheduler complete.');})->purpose('Generate recurring tasks and record due reminders');
\Illuminate\Support\Facades\Schedule::command('tasks:tick')->everyMinute()->withoutOverlapping();

Artisan::command('finance:tick',function(){\App\Services\FinanceAccess::tick();$this->info('Finance scheduler complete.');});
\Illuminate\Support\Facades\Schedule::command('finance:tick')->everyMinute()->withoutOverlapping();
