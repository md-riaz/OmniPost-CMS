@extends('tyro-dashboard::layouts.admin')

@section('title', 'Approver Queue')

@section('content')
<div class="page-header"><h1 class="page-title">Approver Queue</h1></div>
<div class="card"><div class="card-body">
<table class="table">
<thead><tr><th>ID</th><th>Title</th><th>Brand</th><th>Campaign</th><th>Approval Due</th><th>SLA</th></tr></thead>
<tbody>
@foreach($items as $post)
@php($overdue = $post->approval_due_at && $post->approval_due_at->isPast())
<tr>
<td><a href="{{ route('tyro-dashboard.resources.show', ['resource' => 'posts', 'id' => $post->id]) }}">#{{ $post->id }}</a></td>
<td>{{ $post->title }}</td><td>{{ $post->brand?->name }}</td><td>{{ $post->campaign?->name ?? '-' }}</td>
<td>{{ optional($post->approval_due_at)->toDateTimeString() ?? '-' }}</td>
<td><span class="badge {{ $overdue ? 'badge-danger' : 'badge-success' }}">{{ $overdue ? 'Overdue' : 'On time' }}</span></td>
</tr>
@endforeach
</tbody>
</table>
{{ $items->links() }}
</div></div>
@endsection
