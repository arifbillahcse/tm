@extends('layouts.shell')
@section('title','Invoice')
@section('content')
<div class="page-heading"><h1>Invoice #{{ $invoice->id }}</h1><a class="btn secondary" href="{{ route('finance.index') }}">Back to finance</a></div><x-ui.card><article class="crm-print"><h2>{{ $w->name }}</h2><p>Invoice #{{ $invoice->id }}</p><p>Customer: {{ $invoice->customer_name }}</p><p>Issued {{ $invoice->issue_date }} · Due {{ $invoice->due_date }}</p><h3>{{ $invoice->title }}</h3><p style="white-space:pre-wrap">{{ $invoice->description }}</p><h3>{{ $invoice->currency }} {{ \App\Services\FinanceAccess::money($invoice->amount_minor) }}</h3><p>{{ $invoice->transaction_id?'Paid':'Unpaid' }}</p></article></x-ui.card><p>Use browser print to save as PDF.</p>
@endsection
