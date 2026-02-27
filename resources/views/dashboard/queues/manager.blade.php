@extends('tyro-dashboard::layouts.admin')

@section('title', 'Manager Queue')

@section('content')
<div class="page-header"><h1 class="page-title">Manager Queue</h1></div>
<div class="card"><div class="card-body">
<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;">
  <div class="card"><div class="card-body"><h3>Overdue Approvals</h3><p style="font-size:2rem;">{{ $overdueApprovals }}</p></div></div>
  <div class="card"><div class="card-body"><h3>Failed Publishes (24h)</h3><p style="font-size:2rem;">{{ $failedPublishes }}</p></div></div>
</div>
<div style="margin-top:1rem;display:flex;gap:.5rem;">
  <a class="btn btn-primary" href="{{ route('dashboard.queues.approver') }}">Open Approver Queue</a>
  <a class="btn btn-primary" href="{{ route('dashboard.queues.publisher') }}">Open Publisher Queue</a>
</div>
</div></div>
@endsection
