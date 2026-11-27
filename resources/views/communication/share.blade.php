@extends('layouts.shell')
@section('title','Share draft')
@section('content')
<x-ui.card><h1>WhatsApp draft ready</h1><p>Nothing has been sent. Opening this link shares the draft with WhatsApp; review the message and send manually.</p><a class="btn" href="{{ $link }}" target="_blank" rel="noopener noreferrer">Open WhatsApp draft</a><a href="{{ route('communication.index') }}">Cancel</a></x-ui.card>
@endsection
