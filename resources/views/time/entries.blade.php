@extends('layouts.shell')
@section('title','Timesheet')
@section('content')
<div class="page-heading"><div><h1>Time entries</h1><p>Completed intervals · {{ $tz }}</p></div><div class="task-tabs"><a class="btn secondary" href="{{ route('time.index') }}">Focus timer</a><a class="btn secondary" href="{{ route('time.report') }}">Reports</a><a class="btn" href="{{ route('time.create') }}">Manual entry</a></div></div>
<x-ui.card>@forelse($entries as $entry)<div class="goal-item"><strong>{{ $entry->title }}</strong><small>{{ $entry->started_at->copy()->setTimezone($tz)->format('M j, Y H:i:s') }} – {{ $entry->ended_at->copy()->setTimezone($tz)->format('M j, Y H:i:s') }}</small><p>{{ $entry->seconds() }} seconds · {{ str_replace('_',' ',$entry->mode) }} · {{ $entry->billable?'Billable':'Non-billable' }}</p><a href="{{ route('time.edit',$entry) }}">Correct entry</a></div>@empty<p>No completed entries.</p>@endforelse{{ $entries->links() }}</x-ui.card>
@endsection
