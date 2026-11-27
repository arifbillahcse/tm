@extends('layouts.shell')
@section('title','Time reports')
@section('content')
<div class="page-heading"><div><h1>Productivity reports</h1><p>{{ $from->toDateString() }} – {{ $to->copy()->subDay()->toDateString() }} · {{ $tz }}</p></div><a class="btn secondary" href="{{ route('time.entries') }}">Timesheet</a></div>
<x-ui.card><form method="GET" class="task-filters"><label>Date<input class="field" type="date" name="date" value="{{ $date->toDateString() }}"></label><label>Period<select class="field" name="period">@foreach(['day','week','month'] as $p)<option @selected($period===$p)>{{ $p }}</option>@endforeach</select></label><button class="btn">Apply</button></form></x-ui.card>
<div class="stats-grid">@foreach(['work'=>'Work hours','break'=>'Break hours','billable'=>'Billable hours','nonbillable'=>'Non-billable hours'] as $key=>$label)<x-ui.stat :label="$label" :value="number_format($report['totals'][$key]/3600,2)" detail="Completed intervals"/>@endforeach</div>
<x-ui.card><h2>Daily timesheet</h2><div class="time-table"><table><thead><tr><th>Date</th><th>Work</th><th>Break</th><th>Billable</th><th>Non-billable</th></tr></thead><tbody>@forelse($report['days'] as $day=>$totals)<tr><td>{{ $day }}</td>@foreach(['work','break','billable','nonbillable'] as $key)<td>{{ number_format($totals[$key]/3600,2) }} h</td>@endforeach</tr>@empty<tr><td colspan="5">No completed time in this period.</td></tr>@endforelse</tbody></table></div><p>Intervals crossing midnight are split by local day. Breaks are excluded from work and billing totals.</p></x-ui.card>
@endsection
