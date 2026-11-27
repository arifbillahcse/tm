@extends('identity.layout')
@section('content')
<h2>Two-factor authentication</h2><form class="space-y-3 border-b border-slate-700 pb-5" method="POST" action="/two-factor-challenge">@csrf  <label class="block">Authenticator or recovery code<input class="field" name="code" type="text" ></label><button class="btn" type="submit">Verify</button></form>
@endsection
