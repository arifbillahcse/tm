@extends(auth()->check() ? 'layouts.shell' : 'layouts.guest')
@section('title','Settings')
@section('content')
<x-ui.card class="identity-content">@yield('identity')</x-ui.card>
@endsection
