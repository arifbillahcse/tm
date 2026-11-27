@extends('identity.layout')
@section('identity')
<h2>Accept invitation</h2>@guest<p>If you already have an account, sign in first, then reopen the invitation link.</p>@endguest <form class="space-y-3 border-b border-slate-700 pb-5" method="POST" action="/invite">@csrf  <input type="hidden" name="token" value="{{ request('token') }}">@guest <label class="block">Full name<input class="field" name="name" type="text" ></label><label class="block">Password (12 characters minimum)<input class="field" name="password" type="password" autocomplete="off"></label><label class="block">Confirm password<input class="field" name="password_confirmation" type="password" autocomplete="off"></label> @endguest <button class="btn" type="submit">Accept invitation</button></form>
@endsection
