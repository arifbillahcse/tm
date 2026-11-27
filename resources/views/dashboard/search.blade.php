@extends('layouts.shell')
@section('title','Search')
@section('content')
<div class="page-heading"><div><h1>Search</h1><p>Find available pages and workspaces you can access.</p></div></div>
<x-ui.card><form method="GET" action="{{ route('search') }}" class="search-form"><label for="search-q">Search query</label><input class="field" id="search-q" name="q" value="{{ $query }}" maxlength="100"><button class="btn">Search</button></form></x-ui.card>
<x-ui.card><h2>Results</h2>@forelse($pages as $label=>$url)<a class="result-row" href="{{ $url }}">{{ $label }} <span>Page →</span></a>@empty @endforelse @foreach($results as $result)<a class="result-row" href="{{ route('workspaces.show',$result) }}">{{ $result->name }} <span>{{ $result->type }} →</span></a>@endforeach @if($pages->isEmpty()&&$results->isEmpty())<x-ui.empty :heading="$query===''?'Start searching':'No matching results'" description="Try a page or workspace name. Task and document search will arrive with those modules."/>@endif</x-ui.card>
@endsection
