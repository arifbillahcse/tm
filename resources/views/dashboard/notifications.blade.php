@extends('layouts.shell')
@section('title','Notifications')
@section('content')
<div class="page-heading"><div><h1>Notifications</h1><p>Activity in {{ $w->name }}. Live alerts and read tracking are planned for a later phase.</p></div></div>
<x-ui.card><h2>Recent workspace activity</h2>@forelse($events as $event)<div class="activity-row"><span class="activity-dot"></span><div><strong>{{ ucfirst(str_replace('_',' ',$event->event)) }}</strong><small>{{ \Illuminate\Support\Carbon::parse($event->created_at,'UTC')->setTimezone(auth()->user()->timezone ?: 'UTC')->format('M j, H:i') }}</small></div></div>@empty<x-ui.empty heading="No recent activity" description="Your own workspace activity will appear here."/>@endforelse</x-ui.card>
@endsection
