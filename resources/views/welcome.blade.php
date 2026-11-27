@extends('layouts.guest')
@section('content')
<x-ui.card><p class="eyebrow">YOUR PERSONAL &amp; BUSINESS OS</p><h1>Softorio Workspace</h1><p class="mt-3">Personal productivity, professional growth and team management.</p><a class="btn mt-5" href="{{ auth()->check()?route('dashboard'):route('login') }}">{{ auth()->check()?'Open dashboard':'Sign in' }}</a><p class="mt-4 text-sm">Private, invitation-only workspaces.</p></x-ui.card>
@endsection
