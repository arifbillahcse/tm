@extends('layouts.shell')
@section('title','Technology asset')
@section('content')
<div class="page-heading"><div><h1>{{ $asset?'Edit':'Create' }} {{ ucfirst($kind) }}</h1><p>Never enter passwords, API tokens, private keys or confidential access instructions.</p></div><a class="btn secondary" href="{{ route('technology.index') }}">Back</a></div>
<x-ui.card><form class="identity-content" method="POST" action="{{ $asset?route('technology.update',$asset->id):route('technology.store') }}">@csrf
@if($asset)@method('PUT')<input type="hidden" name="version" value="{{ $asset->version }}">@else<input type="hidden" name="kind" value="{{ $kind }}">@endif
<label>Name<input class="field" name="name" required maxlength="200" value="{{ old('name',$asset->name??'') }}"></label>
<label>Status<select class="field" name="status">@foreach(\App\Services\TechnologyAccess::KINDS[$kind] as $s)<option value="{{ $s }}" @selected(old('status',$asset->status??'')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></label>
@if(isset(\App\Services\TechnologyAccess::PARENTS[$kind]))<label>Related asset<select class="field" name="parent_id"><option value="">None</option>@foreach($choices as $a)<option value="{{ $a->id }}" @selected(old('parent_id',$asset->parent_id??null)==$a->id)>{{ $a->name }} ({{ $a->kind }})</option>@endforeach</select></label>@endif
<label>Due / expiry / renewal date<input class="field" type="date" name="due_date" value="{{ old('due_date',$asset->due_date??'') }}"></label>
@foreach(\App\Http\Controllers\TechnologyController::FIELDS[$kind] as $field)
<label>{{ ucfirst(str_replace('_',' ',$field)) }}
@if(in_array($field,['severity','server_type'],true))<select class="field" name="details[{{ $field }}]"><option value="">Not specified</option>@foreach($field==='severity'?['low','medium','high','critical']:['vps','dedicated','cloud','shared','local'] as $v)<option @selected(old('details.'.$field,$details[$field]??'')===$v)>{{ $v }}</option>@endforeach</select>
@else<input class="field" name="details[{{ $field }}]" type="{{ in_array($field,['cpu_cores','ram_gb','disk_gb'],true)?'number':(in_array($field,['url','website'],true)?'url':'text') }}" value="{{ old('details.'.$field,$details[$field]??'') }}" @if($kind==='domain'&&$field==='hostname') required placeholder="www.your-domain.com" @endif>@endif
</label>@endforeach
<label>Notes / documentation<textarea class="field" name="description" rows="8" maxlength="12000">{{ old('description',$asset->description??'') }}</textarea></label><button class="btn">Save asset</button></form></x-ui.card>
@endsection
