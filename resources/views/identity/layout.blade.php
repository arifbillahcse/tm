@auth
@extends('layouts.shell')
@section('title','Settings')
@section('content')
<x-ui.card class="identity-content">@yield('identity')</x-ui.card>
@endsection
@else
<!doctype html><html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Softorio Workspace</title>@vite(['resources/css/app.css','resources/js/app.js'])</head><body><main class="auth-page"><div class="panel auth-card"><div class="brand"><span class="brand-mark">S</span><span>Softorio<span class="brand-sub">WORKSPACE</span></span><button class="icon-button" type="button" data-theme-toggle aria-label="Switch color theme">◐</button></div>@include('layouts.messages')<div class="identity-content">@yield('identity')</div></div></main></body></html>
@endauth
