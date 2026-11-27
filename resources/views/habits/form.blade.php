@extends('layouts.shell')
@section('title','Habit editor')
@section('content')
<div class="page-heading"><h1>{{ $habit->exists?'Edit habit':'New habit' }}</h1><a class="btn secondary" href="{{ route('habits.index') }}">Habits</a></div>
<x-ui.card><form class="identity-content" method="POST" action="{{ $habit->exists?route('habits.update',$habit):route('habits.store') }}">@csrf @if($habit->exists) @method('PUT') @endif<label>Title<input class="field" name="title" required maxlength="200" value="{{ old('title',$habit->title) }}"></label><label>Description<textarea class="field" name="description">{{ old('description',$habit->description) }}</textarea></label><label>State<select class="field" name="active"><option value="1" @selected((bool)old('active',$habit->active))>Active</option><option value="0" @selected(!(bool)old('active',$habit->active))>Paused</option></select></label><p>Habits track daily completion in the timezone set at creation. Paused days count as missed days for streaks.</p><button class="btn">Save habit</button></form></x-ui.card>
@if($habit->exists)<x-ui.card><form method="POST" action="{{ route('habits.destroy',$habit) }}">@csrf @method('DELETE')<button class="btn secondary">Delete habit and history</button></form></x-ui.card>@endif
@endsection
