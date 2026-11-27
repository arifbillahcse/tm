@extends('layouts.shell')
@section('title','AI & analytics')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PHASE 17 · INTELLIGENCE</p><h1>AI &amp; analytics</h1><p>Deterministic insights work even when local AI is disabled.</p></div><a class="btn secondary" href="{{ route('intelligence.settings') }}">AI configuration</a></div>
<div class="stats-grid"><x-ui.stat label="AI inference" :value="$enabled?'Enabled':'Disabled'" detail="Optional local Ollama"/><x-ui.stat label="Model" :value="$model" detail="Server configured"/><x-ui.stat label="Workspace" :value="$workspace->name" detail="Authorized context"/></div>
<x-ui.card><div class="section-heading"><div><h2>Analytics summary</h2><p>Calculated from authorized workspace records.</p></div><a class="btn secondary" href="{{ route('intelligence.summary') }}">Download summary JSON</a></div>
@foreach($report as $section=>$values)<h3 class="section-heading">{{ ucwords(str_replace('_',' ',$section)) }}</h3><div class="stats-grid">@foreach($values as $key=>$value)@if(is_scalar($value))<x-ui.stat :label="ucwords(str_replace('_',' ',$key))" :value="$value" detail="Current reporting period"/>@elseif(is_array($value))<x-ui.stat :label="ucwords(str_replace('_',' ',$key))" :value="count($value).' entries'" detail="Scoped records"/>@endif @endforeach</div>@endforeach
</x-ui.card>
@if($plan)<x-ui.card><div class="section-heading"><div><h2>Daily planning recommendations</h2><p>Deterministic ranking; no AI call required.</p></div><a class="btn secondary" href="{{ route('intelligence.plan') }}">View plan JSON</a></div>
<h3>Priority tasks</h3>@forelse($plan['tasks'] as $task)<div class="result-row"><strong>{{ $task->title }}</strong><small>{{ ucfirst($task->priority) }} · {{ $task->due_at?->timezone(auth()->user()->timezone?:'UTC')->format('M j, H:i')??'No deadline' }}</small></div>@empty<p>No eligible open tasks.</p>@endforelse
<h3>Active goals</h3>@forelse($plan['goals'] as $goal)<div class="result-row"><strong>{{ $goal->title }}</strong><small>{{ $goal->actual }} / {{ $goal->target }} {{ $goal->unit }}</small></div>@empty<p>No active goals.</p>@endforelse
<h3>Skill gaps</h3>@forelse($plan['skills'] as $skill)<div class="result-row"><strong>{{ $skill->name }}</strong><small>{{ $skill->current_level }} / {{ $skill->target_level }}</small></div>@empty<p>No recorded skill gaps.</p>@endforelse
</x-ui.card>
<x-ui.card><h2>Ask your second brain</h2><p>Only matching, unarchived notes in your private workspace are eligible. Emails, phone numbers and credential-like strings are redacted before local inference.</p><form id="ai-question" class="form-stack"><label for="ai-question-text">Your question</label><textarea id="ai-question-text" class="field" maxlength="2000" minlength="3" required></textarea><button class="btn" type="submit" @disabled(!$enabled)>Ask local AI</button></form><pre id="ai-answer" class="result-row" hidden aria-live="polite"></pre></x-ui.card>
@endif
<script>
document.getElementById('ai-question')?.addEventListener('submit',async e=>{e.preventDefault();const out=document.getElementById('ai-answer');out.hidden=false;out.textContent='Thinking…';try{const token=document.querySelector('meta[name="csrf-token"]')?.content;const response=await fetch(@json(route('intelligence.ask')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token||''},body:JSON.stringify({question:document.getElementById('ai-question-text').value})});const data=await response.json();if(!response.ok)throw new Error(data.message||'AI request failed');out.textContent=data.answer;}catch(err){out.textContent=err.message;}});
</script>
@endsection
