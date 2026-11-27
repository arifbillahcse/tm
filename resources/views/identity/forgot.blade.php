@extends('identity.layout')
@section('identity')
<h2>Reset password</h2><form class="space-y-3 border-b border-slate-700 pb-5" method="POST" action="/forgot-password">@csrf  <label class="block">Email<input class="field" name="email" type="email" ></label><button class="btn" type="submit">Request reset</button></form><a href="/login">Sign in</a>
@endsection
