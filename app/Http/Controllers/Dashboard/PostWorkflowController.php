<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\AddPostCommentRequest;
use App\Http\Requests\Workflow\RejectPostRequest;
use App\Models\Post;
use App\Models\PostComment;
use App\Services\PostStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostWorkflowController extends Controller
{
    public function __construct(
        private PostStatusService $statusService
    ) {}

    public function submitForApproval(Request $request, Post $post)
    {
        Gate::authorize('submitForApproval', $post);

        try {
            $this->statusService->submitForApproval($post, Auth::user());
            
            return redirect()
                ->route('tyro-dashboard.resources.show', ['resource' => 'posts', 'id' => $post->id])
                ->with('success', 'Post submitted for approval successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, Post $post)
    {
        Gate::authorize('approve', $post);

        try {
            $this->statusService->approve($post, Auth::user());
            
            return redirect()
                ->route('tyro-dashboard.resources.show', ['resource' => 'posts', 'id' => $post->id])
                ->with('success', 'Post approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(RejectPostRequest $request, Post $post)
    {
        Gate::authorize('reject', $post);

        try {
            $this->statusService->reject($post, Auth::user(), $request->validated('reason'));
            
            return redirect()
                ->route('tyro-dashboard.resources.show', ['resource' => 'posts', 'id' => $post->id])
                ->with('success', 'Post rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addComment(AddPostCommentRequest $request, Post $post)
    {
        Gate::authorize('comment', $post);

        $validated = $request->validated();

        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'comment_text' => $validated['comment_text'],
        ]);

        // Notify post creator about new comment if it's not their own comment
        if ($post->created_by !== Auth::id() && $post->creator) {
            $post->creator->notify(new \App\Notifications\PostCommentAdded($post, Auth::user(), $comment));
        }

        return back()->with('success', 'Comment added successfully.');
    }

    public function showComments(Post $post)
    {
        Gate::authorize('view', $post);

        $comments = $post->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return view('dashboard.post-comments', compact('post', 'comments'));
    }
}
