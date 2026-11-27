@extends('layouts.shell')
@section('title','Conversation')
@section('content')
<div class="page-heading"><div><h1>{{ $conversation->name }}</h1><p>{{ $members->pluck('name')->implode(', ') }}</p></div><a class="btn secondary" href="{{ route('communication.index') }}">Back</a></div>
<x-ui.card><h2>Messages</h2>{{ $messages->links() }}@forelse($messages->getCollection()->reverse() as $m)<div class="goal-item"><strong>{{ $names[$m->user_id]??'Former member' }}</strong><small>{{ $m->created_at }} UTC</small><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $m->body }}</p>@foreach($mentions->get($m->id,collect()) as $mention)<small>@ {{ $names[$mention->user_id]??'Former member' }}</small>@endforeach</div>@empty<p>No messages yet.</p>@endforelse
<form class="identity-content" method="POST" action="{{ route('communication.message',$conversation->id) }}">@csrf<label>Message<textarea class="field" name="body" rows="4" required maxlength="8000"></textarea></label><fieldset><legend>Notify mentions</legend>@foreach($members as $m)@if($m->id!==auth()->id())<label><input type="checkbox" name="mentions[]" value="{{ $m->id }}"> {{ $m->name }}</label>@endif @endforeach</fieldset><button class="btn">Send message</button></form></x-ui.card>
@endsection
