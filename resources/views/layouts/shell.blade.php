<!doctype html>
<html lang="en" data-theme="light">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>@yield('title','Workspace') · Softorio</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="workspace-app">
<a class="skip" href="#main-content">Skip to content</a>
<div class="app-shell" data-shell>
 <button class="mobile-backdrop" data-sidebar-close aria-label="Close navigation" hidden></button>
 <aside class="sidebar" id="sidebar" aria-label="Main navigation">
  <a class="brand" aria-label="Softorio dashboard" href="{{ route('dashboard') }}"><span class="brand-mark" aria-hidden="true">S</span><span class="nav-label">softorio<span class="brand-sub">WORKSPACE</span></span></a>
  <div class="workspace-picker">
   <span class="eyebrow nav-label">CURRENT WORKSPACE</span>
   <details><summary aria-label="Choose workspace"><span class="workspace-avatar">{{ mb_substr($shellCurrent->name,0,1) }}</span><span class="nav-label"><strong>{{ $shellCurrent->name }}</strong><small>{{ ucfirst($shellCurrent->type) }} workspace</small></span><span class="nav-label" aria-hidden="true">⌄</span></summary>
    <div class="picker-menu">@foreach($shellWorkspaces as $item)<form method="POST" action="{{ route('workspace.switch',$item) }}">@csrf<button type="submit" class="picker-option" @if($item->id===$shellCurrent->id) aria-current="true" @endif>{{ $item->name }} <small>{{ $item->type }}</small></button></form>@endforeach<a href="{{ route('workspaces.index') }}">Manage workspaces</a></div>
   </details>
  </div>
  <nav class="nav-stack"><span class="eyebrow nav-label">WORKSPACE</span>
   <x-ui.nav :href="route('dashboard')" :active="request()->routeIs('dashboard')" aria-label="Overview"><span aria-hidden="true">▦</span><span class="nav-label">Overview</span></x-ui.nav>
   <x-ui.nav :href="route('search')" :active="request()->routeIs('search')" aria-label="Search"><span aria-hidden="true">⌕</span><span class="nav-label">Search</span></x-ui.nav>
   <x-ui.nav :href="route('notifications.index')" :active="request()->routeIs('notifications.index')" aria-label="Notifications"><span aria-hidden="true">◉</span><span class="nav-label">Notifications</span></x-ui.nav>
   <span class="eyebrow nav-label">MANAGE</span>
   <x-ui.nav :href="route('workspaces.index')" :active="request()->routeIs('workspaces.index','workspaces.show')" aria-label="Workspaces"><span aria-hidden="true">▤</span><span class="nav-label">Workspaces</span></x-ui.nav>
   <x-ui.nav :href="route('profile')" :active="request()->routeIs('profile')" aria-label="Profile and security"><span aria-hidden="true">⚙</span><span class="nav-label">Profile &amp; security</span></x-ui.nav>
   @if($shellCurrent->type==='personal')<span class="eyebrow nav-label">PRODUCTIVITY</span><x-ui.nav :href="route('tasks.index')" :active="request()->routeIs('tasks.*')" aria-label="Tasks"><span aria-hidden="true">✓</span><span class="nav-label">Tasks</span></x-ui.nav><x-ui.nav :href="route('tasks.index',['view'=>'today'])" aria-label="My Day"><span aria-hidden="true">☀</span><span class="nav-label">My Day</span></x-ui.nav><x-ui.nav :href="route('calendar.index')" :active="request()->routeIs('calendar.*')" aria-label="Calendar"><span aria-hidden="true">▦</span><span class="nav-label">Calendar</span></x-ui.nav><x-ui.nav :href="route('goals.index')" :active="request()->routeIs('goals.*')" aria-label="Goals"><span aria-hidden="true">◎</span><span class="nav-label">Goals</span></x-ui.nav><x-ui.nav :href="route('habits.index')" :active="request()->routeIs('habits.*')" aria-label="Habits"><span aria-hidden="true">✓</span><span class="nav-label">Habits</span></x-ui.nav><x-ui.nav :href="route('time.index')" :active="request()->routeIs('time.*')" aria-label="Focus and time"><span aria-hidden="true">◷</span><span class="nav-label">Focus &amp; time</span></x-ui.nav><x-ui.nav :href="route('knowledge.index')" :active="request()->routeIs('knowledge.*')" aria-label="Second brain"><span aria-hidden="true">▤</span><span class="nav-label">Second brain</span></x-ui.nav><x-ui.nav :href="route('career.index')" :active="request()->routeIs('career.*')" aria-label="Career"><span aria-hidden="true">◇</span><span class="nav-label">Career</span></x-ui.nav>@endif
   <span class="eyebrow nav-label">COMING NEXT</span>
   @foreach($shellCurrent->type==='personal'?[]:['Team','Projects','CRM','Finance','Infrastructure'] as $label)
    <span class="nav-link unavailable nav-label">{{ $label }}<small>Soon</small></span>
   @endforeach
  </nav>
  <div class="sidebar-bottom"><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link" aria-label="Sign out"><span aria-hidden="true">↪</span><span class="nav-label">Sign out</span></button></form></div>
 </aside>
 <div class="app-body">
  <header class="topbar">
   <button class="icon-button" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Toggle navigation">☰</button>
   <span class="breadcrumb">Workspace / @yield('title','Overview')</span>
   <div class="top-actions">
    <button class="search-trigger" type="button" data-search-open aria-label="Open search"><span aria-hidden="true">⌕</span><span class="desktop-only">Search pages</span><kbd class="desktop-only">Ctrl K</kbd></button>
    <button class="icon-button" data-theme-toggle type="button" aria-label="Switch color theme">◐</button>
    <a class="icon-button" href="{{ route('notifications.index') }}" aria-label="Notifications">◉</a>
    <a class="user-avatar" href="{{ route('profile') }}" aria-label="Your profile">{{ mb_substr(auth()->user()->name,0,1) }}</a>
   </div>
  </header>
  <main id="main-content" class="page-content" tabindex="-1">
   @include('layouts.messages')
   @yield('content')
  </main>
 </div>
</div>
<dialog id="search-dialog" class="search-dialog" aria-labelledby="search-title"><form method="GET" action="{{ route('search') }}"><div class="dialog-heading"><h2 id="search-title">Search workspace</h2><button type="button" data-search-close aria-label="Close search">×</button></div><label for="quick-search">Search pages or accessible workspaces</label><input id="quick-search" class="field" name="q" maxlength="100" autofocus><button class="btn">Search</button></form></dialog>
</body></html>
