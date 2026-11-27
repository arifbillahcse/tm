@extends('layouts.shell')
@section('title','Calendar')
@section('content')
<div class="page-heading"><div><h1>Daily planner &amp; calendar</h1><p>{{ $tz }} · Schedule focused work and appointments.</p></div><div class="task-tabs"><a class="btn secondary" href="{{ route('calendar.templates') }}">Templates</a><a class="btn" href="{{ route('calendar.create',['date'=>$date->toDateString()]) }}">+ New event</a></div></div>
<x-ui.card><div class="task-tabs"><a class="btn secondary" href="{{ route('calendar.index',['date'=>$prev->toDateString(),'view'=>$view]) }}">Previous</a><a class="btn secondary" href="{{ route('calendar.index',['view'=>$view]) }}">Today</a><a class="btn secondary" href="{{ route('calendar.index',['date'=>$to->toDateString(),'view'=>$view]) }}">Next</a>@foreach(['day','week','month'] as $mode)<a class="btn {{ $view===$mode?'':'secondary' }}" href="{{ route('calendar.index',['date'=>$date->toDateString(),'view'=>$mode]) }}">{{ ucfirst($mode) }}</a>@endforeach</div><h2>{{ $from->format('M j, Y') }} – {{ $to->copy()->subDay()->format('M j, Y') }}</h2></x-ui.card>
<div class="calendar-grid calendar-{{ $view }}">
@foreach($days as $day)<x-ui.card class="calendar-day" data-calendar-date="{{ $day['date']->toDateString() }}"><div class="section-heading"><h3>{{ $day['date']->format('D, M j') }}</h3><a aria-label="Add event on {{ $day['date']->toDateString() }}" href="{{ route('calendar.create',['date'=>$day['date']->toDateString()]) }}">+</a></div>
@foreach($day['events'] as $o)<div class="calendar-event" @if(!$o['event']->recurrence) draggable="true" data-calendar-event="{{ $o['event']->id }}" data-calendar-move="{{ route('calendar.move',$o['event']) }}" @endif><a href="{{ route('calendar.edit',$o['event']) }}"><small>{{ $o['start']->copy()->setTimezone($tz)->format('H:i') }}–{{ $o['end']->copy()->setTimezone($tz)->format('H:i') }} · {{ $o['event']->kind }}</small><strong>{{ $o['event']->title }}</strong>@if($o['event']->recurrence)<small>Repeating</small>@endif</a></div>@endforeach
@foreach($day['tasks'] as $task)<a class="calendar-task" href="{{ route('tasks.show',$task) }}">✓ {{ $task->title }} <small>Task</small></a>@endforeach
@if(!$day['events']&&!$day['tasks']->count())<p class="calendar-empty">Open schedule</p>@endif
</x-ui.card>@endforeach
</div>
<form id="calendar-move-form" method="POST" hidden>@csrf<input name="date" type="hidden"></form>
<x-ui.card><h2>Apply a daily template</h2>@forelse($templates as $template)<form class="task-filters" method="POST" action="{{ route('calendar.templates.apply',$template) }}">@csrf<strong>{{ $template->name }}</strong><label>Date<input class="field" type="date" name="date" required value="{{ $date->toDateString() }}"></label><button class="btn">Apply template</button></form>@empty<p>Create a reusable daily plan in Templates.</p>@endforelse</x-ui.card>
@endsection
