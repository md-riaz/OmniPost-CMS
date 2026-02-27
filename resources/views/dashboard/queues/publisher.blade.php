@extends('tyro-dashboard::layouts.admin')

@section('title', 'Publisher Queue')

@section('content')
<div class="page-header"><h1 class="page-title">Publisher Queue</h1></div>
<div class="card"><div class="card-body">
<table class="table">
<thead><tr><th>ID</th><th>Post</th><th>Brand</th><th>Campaign</th><th>Status</th><th>Scheduled</th></tr></thead>
<tbody>
@foreach($items as $variant)
<tr>
<td><a href="{{ route('tyro-dashboard.resources.show', ['resource' => 'post-variants', 'id' => $variant->id]) }}">#{{ $variant->id }}</a></td>
<td>{{ $variant->post?->title }}</td><td>{{ $variant->post?->brand?->name }}</td><td>{{ $variant->post?->campaign?->name ?? '-' }}</td><td>{{ $variant->status }}</td><td>{{ optional($variant->scheduled_at)->toDateTimeString() ?? '-' }}</td>
</tr>
@endforeach
</tbody>
</table>
{{ $items->links() }}
</div></div>
@endsection
