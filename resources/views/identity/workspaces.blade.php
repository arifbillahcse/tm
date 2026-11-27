@extends('identity.layout')
@section('identity')
<h2>Your workspaces</h2><ul class="space-y-2">@foreach($workspaces as $workspace)<li><a href="{{ route('workspaces.show',$workspace) }}">{{ $workspace->name }} · {{ $workspace->type }} · {{ $workspace->pivot->role }}</a></li>@endforeach</ul><h2>Create business workspace</h2><form class="space-y-3 border-b border-slate-700 pb-5" method="POST" action="/workspaces">@csrf  <label class="block">Workspace name<input class="field" name="name" type="text" ></label><button class="btn" type="submit">Create</button></form>
@endsection
