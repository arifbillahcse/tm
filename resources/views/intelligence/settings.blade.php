@extends('layouts.shell')
@section('title','AI configuration')
@section('content')
<div class="page-heading"><div><p class="eyebrow">INTELLIGENCE</p><h1>AI configuration</h1><p>Local inference is optional. The application remains functional without it.</p></div><a class="btn secondary" href="{{ route('intelligence.index') }}">Back to analytics</a></div>
<x-ui.card><div class="stats-grid"><x-ui.stat label="Local AI status" :value="$enabled?'Enabled':'Disabled'" detail="OLLAMA_ENABLED server setting"/><x-ui.stat label="Configured model" :value="$model" detail="OLLAMA_MODEL server setting"/><x-ui.stat label="Endpoint" :value="$endpoint" detail="Fixed loopback endpoint"/></div><p>For security, model endpoint and activation are controlled by the server operator in environment configuration. They cannot be changed from a browser request. Never expose Ollama on a public interface.</p><pre class="result-row">OLLAMA_ENABLED=false
OLLAMA_MODEL=qwen2.5:3b</pre></x-ui.card>
@endsection
