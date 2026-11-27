@extends('layouts.shell')
@section('title','Habit history')
@section('content')
<div class="page-heading"><h1>{{ $habit->title }}</h1><a class="btn secondary" href="{{ route('habits.index') }}">Habits</a></div>
<x-ui.card><p>Current streak {{ $stats['current'] }} · Best {{ $stats['best'] }} · Completed days {{ $stats['total'] }}</p>@if($habit->active)<form method="POST" class="task-filters" action="{{ route('habits.check',$habit) }}">@csrf<label>Date<input class="field" name="date" type="date" min="{{ $habit->start_date->toDateString() }}" max="{{ $stats['today'] }}" value="{{ $stats['today'] }}" required></label><label>Check-in<select class="field" name="completed"><option value="1">Complete</option><option value="0">Undo</option></select></label><button class="btn">Save check-in</button></form>@endif</x-ui.card>
<x-ui.card><h2>Completed dates</h2>@forelse($entries as $entry)<p class="result-row">{{ $entry->local_date->toDateString() }} · Completed</p>@empty<p>No check-ins yet.</p>@endforelse{{ $entries->links() }}</x-ui.card>
@endsection
