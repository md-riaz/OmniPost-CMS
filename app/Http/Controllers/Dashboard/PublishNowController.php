<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\PublishVariantJob;
use App\Models\PostVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PublishNowController extends Controller
{
    public function __invoke(PostVariant $variant): RedirectResponse
    {
        // Check if already published
        $successfulAttempt = $variant->publicationAttempts()
            ->where('result', 'success')
            ->whereNotNull('external_post_id')
            ->first();

        if ($successfulAttempt) {
            return redirect()->back()->with('warning', 'This variant has already been published.');
        }

        // Check if already publishing
        if ($variant->status === 'publishing') {
            return redirect()->back()->with('warning', 'This variant is already being published.');
        }

        try {
            // Mark as publishing
            $variant->update(['status' => 'publishing']);

            // Dispatch the job immediately
            PublishVariantJob::dispatch($variant->id);

            Log::info('Publish now action triggered', [
                'variant_id' => $variant->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Publishing job has been dispatched.');
        } catch (\Exception $e) {
            Log::error('Failed to dispatch publish now job', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to dispatch publishing job: ' . $e->getMessage());
        }
    }
}
