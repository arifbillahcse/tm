@extends('identity.layout')
@section('content')
<h2>Sign in</h2><form class="space-y-3 border-b border-slate-700 pb-5" method="POST" action="/login">@csrf  <label class="block">Email<input class="field" name="email" type="email" ></label><label class="block">Password<input class="field" name="password" type="password" autocomplete="off"></label><button class="btn" type="submit">Sign in</button></form><a href="/forgot-password">Forgot password?</a><p>Accounts are invitation-only.</p>
@endsection
