<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class SessionVersion {
 public function handle(Request $request,Closure $next){
  if($request->user()&&$request->session()->get('auth_version')!==$request->user()->auth_version){
   Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');
  }
  return $next($request);
 }
}
