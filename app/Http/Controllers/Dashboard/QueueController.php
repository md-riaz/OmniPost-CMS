<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostVariant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function editor(Request $request): View
    {
        $this->authorizeQueue();

        $items = Post::query()
            ->with(['brand', 'campaign'])
            ->whereIn('status', ['draft', 'failed'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('dashboard.queues.editor', compact('items'));
    }

    public function approver(Request $request): View
    {
        $this->authorizeQueue();

        $items = Post::query()
            ->with(['brand', 'campaign'])
            ->where('status', 'pending')
            ->orderBy('approval_due_at')
            ->paginate(20);

        return view('dashboard.queues.approver', compact('items'));
    }

    public function publisher(Request $request): View
    {
        $this->authorizeQueue();

        $items = PostVariant::query()
            ->with(['post.brand', 'post.campaign'])
            ->whereIn('status', ['scheduled', 'failed', 'draft'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('dashboard.queues.publisher', compact('items'));
    }

    public function manager(Request $request): View
    {
        $this->authorizeQueue();

        $overdueApprovals = Post::query()
            ->where('status', 'pending')
            ->whereNotNull('approval_due_at')
            ->where('approval_due_at', '<', now())
            ->count();

        $failedPublishes = PostVariant::query()
            ->where('status', 'failed')
            ->where('updated_at', '>=', now()->subDay())
            ->count();

        return view('dashboard.queues.manager', compact('overdueApprovals', 'failedPublishes'));
    }

    private function authorizeQueue(): void
    {
        abort_unless(auth()->user()?->hasPrivilege('post.view'), 403);
    }
}
