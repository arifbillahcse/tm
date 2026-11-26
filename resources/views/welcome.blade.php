<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="mx-auto max-w-3xl px-6 py-24">
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-300">Phase 2 · Application foundation</p>
        <h1 class="mt-4 text-4xl font-bold tracking-tight">{{ config('app.name') }}</h1>
        <p class="mt-4 text-lg text-slate-300">Personal productivity, professional growth and team management.</p>
        <p class="mt-8 text-sm text-slate-400">Private workspace features will be introduced after authentication and authorization.</p>
    </main>
    @livewireScripts
</body>
</html>
