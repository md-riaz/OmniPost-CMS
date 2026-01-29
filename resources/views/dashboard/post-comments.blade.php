<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Comments - {{ $post->title }} - {{ config('app.name') }}</title>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .back-button {
            padding: 8px 16px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }
        .back-button:hover {
            background: #4f46e5;
        }
        .post-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .comment-form {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            font-family: inherit;
            resize: vertical;
        }
        .comment-form button {
            margin-top: 10px;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .comment-form button:hover {
            background: #4f46e5;
        }
        .comment {
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid #6366f1;
            background: #f9fafb;
            border-radius: 4px;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #6b7280;
        }
        .comment-author {
            font-weight: 600;
            color: #1f2937;
        }
        .comment-text {
            color: #374151;
            line-height: 1.6;
        }
        .reply {
            margin-left: 40px;
            border-left-color: #9ca3af;
        }
        .reply-button {
            margin-top: 10px;
            padding: 5px 10px;
            background: #e5e7eb;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .reply-button:hover {
            background: #d1d5db;
        }
        .status-history {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .status-change {
            padding: 10px;
            margin-bottom: 10px;
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            border-radius: 4px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Comments: {{ $post->title }}</h1>
            <a href="{{ route('tyro-dashboard.resources.show', ['resource' => 'posts', 'id' => $post->id]) }}" class="back-button">← Back to Post</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="post-info">
            <strong>Status:</strong> {{ ucfirst($post->status) }}<br>
            <strong>Brand:</strong> {{ $post->brand->name }}<br>
            <strong>Created by:</strong> {{ $post->creator->name }}
        </div>

        <div class="comment-form">
            <h3>Add Comment</h3>
            <form action="{{ route('dashboard.posts.add-comment', $post) }}" method="POST">
                @csrf
                <textarea name="comment_text" placeholder="Write your comment..." required></textarea>
                @error('comment_text')
                    <div style="color: #dc2626; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
                @enderror
                <button type="submit">Post Comment</button>
            </form>
        </div>

        <h3>Comments ({{ $comments->count() }})</h3>

        @forelse($comments as $comment)
            <div class="comment">
                <div class="comment-header">
                    <span class="comment-author">{{ $comment->user->name }}</span>
                    <span>{{ $comment->created_at->diffForHumans() }}</span>
                </div>
                <div class="comment-text">{{ $comment->comment_text }}</div>
                
                <button class="reply-button" onclick="showReplyForm({{ $comment->id }})">Reply</button>
                
                <div id="reply-form-{{ $comment->id }}" style="display: none; margin-top: 15px;">
                    <form action="{{ route('dashboard.posts.add-comment', $post) }}" method="POST">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <textarea name="comment_text" placeholder="Write your reply..." required style="min-height: 60px;"></textarea>
                        <button type="submit">Post Reply</button>
                    </form>
                </div>

                @foreach($comment->replies as $reply)
                    <div class="comment reply">
                        <div class="comment-header">
                            <span class="comment-author">{{ $reply->user->name }}</span>
                            <span>{{ $reply->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="comment-text">{{ $reply->comment_text }}</div>
                    </div>
                @endforeach
            </div>
        @empty
            <p style="color: #6b7280; text-align: center; padding: 40px 0;">No comments yet. Be the first to comment!</p>
        @endforelse

        @if($post->statusChanges->count() > 0)
            <div class="status-history">
                <h3>Status History</h3>
                @foreach($post->statusChanges->sortByDesc('changed_at') as $change)
                    <div class="status-change">
                        <strong>{{ ucfirst($change->from_status) }} → {{ ucfirst($change->to_status) }}</strong><br>
                        @if($change->changedBy)
                            <small>by {{ $change->changedBy->name }}</small><br>
                        @endif
                        <small>{{ $change->changed_at->format('Y-m-d H:i:s') }}</small>
                        @if($change->reason)
                            <div style="margin-top: 5px; font-style: italic;">{{ $change->reason }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        function showReplyForm(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
