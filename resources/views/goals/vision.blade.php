@extends('layouts.shell')
@section('title','Vision board')
@section('content')
<div class="page-heading"><div><h1>My vision board</h1><p>Keep your aspirations and purpose visible.</p></div><a class="btn secondary" href="{{ route('goals.index') }}">Roadmap</a></div>
<div class="dashboard-grid">@forelse($goals as $goal)<x-ui.card class="vision-card"><p class="eyebrow">{{ $goal->category }} · {{ $goal->horizon }}</p><h2>{{ $goal->title }}</h2><p class="task-description">{{ $goal->vision }}</p><progress class="goal-progress" max="100" value="{{ $goal->progress() }}" aria-label="Goal progress"></progress><a href="{{ route('goals.show',$goal) }}">View goal · {{ $goal->progress() }}%</a></x-ui.card>@empty<x-ui.card><p>Add a vision statement to your goals to populate this board.</p></x-ui.card>@endforelse</div>{{ $goals->links() }}
@endsection
