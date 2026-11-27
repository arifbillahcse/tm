@extends('identity.layout')
@section('content')
<h2>Choose a new password</h2><form class="space-y-3 border-b border-slate-700 pb-5" method="POST" action="/reset-password">@csrf  <input type="hidden" name="token" value="{{ request('token') }}"><label>Email<input class="field" type="email" name="email" value="{{ request('email') }}"></label><label class="block">New password<input class="field" name="password" type="password" autocomplete="off"></label><label class="block">Confirm password<input class="field" name="password_confirmation" type="password" autocomplete="off"></label><button class="btn" type="submit">Reset password</button></form>
@endsection
