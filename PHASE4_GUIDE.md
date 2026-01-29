# Phase 4: Publishing Engine - Quick Start Guide

## Overview

Phase 4 implements the complete scheduling and publishing pipeline for OmniPost CMS. Posts can be scheduled to publish at specific times or published immediately via the dashboard.

## Architecture

```
Cron (every minute)
    ↓
SchedulePostsCommand
    ↓
Finds PostVariants where scheduled_at <= now() AND status = 'scheduled'
    ↓
Dispatches PublishVariantJob for each
    ↓
Job publishes to platform via connector
    ↓
Records PublicationAttempt
    ↓
Updates variant status to 'published' or 'failed'
```

## Setup

### 1. Configure Cron

Add this to your crontab:

```bash
* * * * * cd /path/to/omnipost && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Start Queue Worker

Run as a daemon (use supervisor or systemd):

```bash
php artisan queue:work --tries=3 --timeout=120
```

Or for development:

```bash
php artisan queue:listen
```

### 3. Ensure Database Queue Table Exists

If not already created:

```bash
php artisan queue:table
php artisan migrate
```

## Usage

### Scheduling a Post

1. Create a Post in Tyro Dashboard (`/dashboard/resources/posts`)
2. Create a PostVariant in Tyro Dashboard (`/dashboard/resources/post-variants`)
3. Set the following fields:
   - **Post**: Select the parent post
   - **Social Account**: Select connected Facebook/LinkedIn account
   - **Platform**: facebook or linkedin
   - **Custom Text**: Optional text override
   - **Schedule Time**: Date and time to publish
   - **Status**: Set to "scheduled"
4. Save the variant

The post will be automatically published when:
- The scheduled_at time is reached
- The cron runs `posts:schedule` command
- The queue worker processes the job

### Publishing Immediately

1. Go to Post Variants list (`/dashboard/resources/post-variants`)
2. Find the variant you want to publish
3. Click the **"Publish Now"** button (lightning icon)
4. Confirm the action
5. The job is dispatched immediately

## Status Flow

```
draft → scheduled → publishing → published
                              ↓
                           failed (after 3 retries)
```

## Retry Logic

- **Max Attempts**: 3
- **Backoff**: 1 minute, 5 minutes, 15 minutes (exponential)
- **Token Expired** (error 190/401): No retry, mark account as expired
- **Rate Limited** (error 613/429): Retry with backoff
- **Other Errors**: Standard retry with backoff

## Monitoring

### Check Scheduled Jobs

```bash
php artisan posts:schedule
```

### View Queue Status

```bash
php artisan queue:work --once
```

### Check Failed Jobs

```bash
php artisan queue:failed
```

### Retry Failed Jobs

```bash
# Retry all
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>
```

### View Publication Attempts

Go to `/dashboard/resources/publication-attempts` to see all publish attempts with:
- Attempt number
- Result (success/fail)
- External post ID
- Error details
- Raw API responses

## Debugging

### Enable Verbose Logging

Check `storage/logs/laravel.log` for detailed logs:

```bash
tail -f storage/logs/laravel.log
```

### Common Issues

**Job not dispatching:**
- Check cron is running: `crontab -l`
- Run manually: `php artisan posts:schedule`
- Check queue worker is running

**Publishing fails:**
- Check token is valid: `/dashboard/resources/connected-social-accounts`
- Check publication_attempts for error details
- Verify Facebook/LinkedIn credentials in `.env`

**Token expired:**
- Reconnect account via OAuth
- Or run: `php artisan oauth:watch-expiry --refresh`

## Manual Testing

### Create a Test Post

```bash
php artisan tinker
```

```php
// Create a brand
$brand = App\Models\Brand::first();

// Create a post
$post = App\Models\Post::create([
    'brand_id' => $brand->id,
    'created_by' => 1,
    'title' => 'Test Post',
    'base_text' => 'This is a test post from OmniPost CMS!',
    'status' => 'approved',
]);

// Get a connected account
$account = App\Models\ConnectedSocialAccount::where('status', 'connected')->first();

// Create a variant scheduled for immediate publishing
$variant = App\Models\PostVariant::create([
    'post_id' => $post->id,
    'platform' => $account->platform,
    'connected_social_account_id' => $account->id,
    'scheduled_at' => now()->subMinute(), // 1 minute ago
    'status' => 'scheduled',
]);

// Dispatch the scheduler
Artisan::call('posts:schedule');

// Or dispatch directly
App\Jobs\PublishVariantJob::dispatch($variant->id);

// Check status
$variant->refresh();
echo $variant->status; // Should be 'publishing' then 'published'

// Check attempts
$variant->publicationAttempts()->get();
```

## API Integration

### Facebook

Publishes to: `POST https://graph.facebook.com/v18.0/{page_id}/feed`

Parameters:
- `message`: Post text
- `link`: Optional URL
- `access_token`: Page access token

### LinkedIn

Publishes to: `POST https://api.linkedin.com/v2/ugcPosts`

Payload:
```json
{
  "author": "urn:li:organization:123",
  "lifecycleState": "PUBLISHED",
  "specificContent": {
    "com.linkedin.ugc.ShareContent": {
      "shareCommentary": {"text": "Post text"},
      "shareMediaCategory": "NONE"
    }
  },
  "visibility": {
    "com.linkedin.ugc.MemberNetworkVisibility": "PUBLIC"
  }
}
```

## Production Checklist

- [ ] Cron configured and running
- [ ] Queue worker running as daemon (supervisor/systemd)
- [ ] Queue failed jobs table exists (`php artisan queue:failed-table && migrate`)
- [ ] Logs directory is writable
- [ ] Adequate disk space for logs
- [ ] Facebook/LinkedIn OAuth credentials configured
- [ ] At least one brand and connected account created
- [ ] Test post successfully published
- [ ] Monitoring setup for failed jobs
- [ ] Alerts configured for repeated failures

## Next Phase: Workflow & Collaboration

Phase 5 will add:
- Approval workflow enforcement
- Comments and collaboration
- Calendar UI
- Notifications

---

**Questions?** Check `IMPLEMENTATION_STATUS.md` for detailed implementation notes.
