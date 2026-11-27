<?php
use App\Http\Controllers\AuthController as A;
use App\Http\Controllers\WorkspaceController as W;
use App\Http\Middleware\SessionVersion;
use App\Http\Middleware\NoStore;
use Illuminate\Support\Facades\Route;
Route::view('/', 'welcome')->name('home');
Route::middleware(NoStore::class)->group(function(){
 Route::middleware('guest')->group(function(){
  Route::view('/login','identity.login')->name('login');
  Route::post('/login',[A::class,'login'])->middleware('throttle:5,1');
  Route::view('/two-factor-challenge','identity.challenge')->name('challenge');
  Route::post('/two-factor-challenge',[A::class,'challenge'])->middleware('throttle:5,1');
  Route::view('/forgot-password','identity.forgot')->name('password.request');
  Route::post('/forgot-password',[A::class,'forgot'])->middleware('throttle:5,1')->name('password.email');
  Route::view('/reset-password','identity.reset')->name('password.reset');
  Route::post('/reset-password',[A::class,'reset'])->middleware('throttle:5,1')->name('password.update');
 });
 Route::view('/invite','identity.invite')->middleware(SessionVersion::class)->name('invite');
 Route::post('/invite',[W::class,'accept'])->middleware([SessionVersion::class,'throttle:5,1']);
 Route::middleware(['auth',SessionVersion::class])->group(function(){
  Route::get('/dashboard',[\App\Http\Controllers\DashboardController::class,'index'])->name('dashboard');
  Route::get('/search',[\App\Http\Controllers\DashboardController::class,'search'])->middleware('throttle:60,1')->name('search');
  Route::get('/notifications',[\App\Http\Controllers\DashboardController::class,'notifications'])->name('notifications.index');
  Route::post('/workspaces/{workspace}/switch',[\App\Http\Controllers\DashboardController::class,'switch'])->name('workspace.switch');
  Route::post('/logout',[A::class,'logout'])->name('logout');
  Route::get('/profile',[A::class,'profile'])->name('profile');
  Route::post('/profile',[A::class,'updateProfile']);
  Route::post('/profile/2fa/enroll',[A::class,'enroll'])->middleware('throttle:5,1');
  Route::post('/profile/2fa/confirm',[A::class,'confirm'])->middleware('throttle:5,1');
  Route::post('/profile/sessions/revoke',[A::class,'revokeSessions'])->middleware('throttle:5,1');
  Route::get('/workspaces',[W::class,'index'])->name('workspaces.index');
  Route::post('/workspaces',[W::class,'store']);
  Route::get('/workspaces/{workspace}',[W::class,'show'])->name('workspaces.show');
  Route::post('/workspaces/{workspace}/invitations',[W::class,'invite'])->middleware('throttle:10,1');
  Route::delete('/workspaces/{workspace}/invitations/{invitation}',[W::class,'revoke']);
  Route::patch('/workspaces/{workspace}/members/{member}',[W::class,'role']);
  Route::delete('/workspaces/{workspace}/members/{member}',[W::class,'remove']);
 });
});
