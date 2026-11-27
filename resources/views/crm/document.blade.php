@extends('layouts.shell')
@section('title','Sales document')
@section('content')
<div class="page-heading"><div><h1>{{ ucfirst($doc->kind) }} #{{ $doc->id }}</h1><p>{{ $doc->status }}</p></div><a class="btn secondary" href="{{ route('crm.index') }}">Back to CRM</a></div>
<x-ui.card><article class="crm-print"><p>{{ $w->name }}</p><h2>{{ $doc->title }}</h2><p>Prepared for {{ $contact->name }}</p><p>Deal: {{ $deal->title }}</p><p>Issued: {{ $doc->created_at }} UTC</p><p>Valid until: {{ $doc->valid_until??'Not specified' }}</p><div class="task-description" style="white-space:pre-wrap">{{ $doc->body }}</div><h3>Total: {{ $doc->currency }} {{ $doc->amount }}</h3><p>Status: {{ $doc->status }}</p></article></x-ui.card><p>Use your browser print command to save as PDF. Status is manually recorded; no email has been sent by this application.</p>
@endsection
