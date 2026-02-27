@extends('tyro-dashboard::layouts.admin')

@section('title', 'Editor Queue')

@section('content')
<div class="page-header"><h1 class="page-title">Editor Queue</h1></div>
<div class="card"><div class="card-body">
<table class="table">
<thead><tr><th>ID</th><th>Title</th><th>Brand</th><th>Campaign</th><th>Status</th><th>Updated</th></tr></thead>
<tbody>
@foreach($items as $post)
<tr>
<td><a href="{{ route('tyro-dashboard.resources.show', ['resource' => 'posts', 'id' => $post->id]) }}">#{{ $post->id }}</a></td>
<td>{{ $post->title }}</td><td>{{ $post->brand?->name }}</td><td>{{ $post->campaign?->name ?? '-' }}</td><td>{{ $post->status }}</td><td>{{ $post->updated_at }}</td>
</tr>
@endforeach
</tbody>
</table>
{{ $items->links() }}
</div></div>
@endsection
