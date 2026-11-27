@extends('layouts.shell')
@section('title','Career record editor')
@section('content')
<div class="page-heading"><h1>{{ $record->exists?'Edit':'New' }} {{ $type['label'] }}</h1><a class="btn secondary" href="{{ route('career.index') }}">Career center</a></div>
<x-ui.card><form method="POST" class="identity-content" action="{{ $record->exists?route('career.update',$record):route('career.store') }}">@csrf @if($record->exists) @method('PUT')<input type="hidden" name="version" value="{{ $record->version }}">@endif<input type="hidden" name="kind" value="{{ $record->kind }}">
<label>Title<input class="field" name="title" required maxlength="200" value="{{ old('title',$record->title) }}"></label><label>Status<select class="field" name="status">@foreach($type['states'] as $state)<option @selected(old('status',$record->status)===$state)>{{ $state }}</option>@endforeach</select></label><label>Target / follow-up date<input class="field" type="date" name="target_date" value="{{ old('target_date',$record->target_date?->toDateString()) }}"></label><label>Description<textarea class="field" name="description" rows="4">{{ old('description',$record->description) }}</textarea></label>
@foreach($type['fields'] as $key=>$format)
@php($value=old('details.'.$key,$record->details[$key]??''))
<label>{{ ucfirst(str_replace('_',' ',$key)) }}
@if($format==='long')<textarea class="field" rows="7" name="details[{{ $key }}]">{{ $value }}</textarea>
@elseif($format==='section')<select class="field" name="details[{{ $key }}]"><option value="">Select section</option>@foreach(['listening','reading','writing','speaking','overall'] as $section)<option @selected($section===$value)>{{ $section }}</option>@endforeach</select>
@elseif($format==='band'||$format==='percent')<input class="field" type="number" min="0" max="{{ $format==='band'?9:100 }}" step="{{ $format==='band'?'0.5':'1' }}" name="details[{{ $key }}]" value="{{ $value }}">
@else<input class="field" type="{{ in_array($format,['date','email','url'],true)?$format:'text' }}" name="details[{{ $key }}]" value="{{ $value }}">
@endif</label>@endforeach<button class="btn">Save record</button></form></x-ui.card>
@endsection
