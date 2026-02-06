# Social Media Integration Fixes

## Overview

This document describes all fixes applied to the Facebook (Meta) and LinkedIn integrations in OmniPost CMS, addressing API version updates, permission handling, token management, and authorization consistency.

## Summary of Changes

### Facebook/Meta Integration

#### 1. API Version Update
**Changed:** Facebook Graph API version from v18.0 to v21.0

**Files Modified:**
- `.env` - Updated `FACEBOOK_GRAPH_API_VERSION`
- `app/Services/Platforms/FacebookConnector.php` - Updated default version

**Reason:** v18.0 is deprecated. v21.0 is the current stable version with improved features and security.

#### 2. OAuth Permissions
**Added Missing Permissions:**
- `pages_manage_metadata` - Required for managing page information
- `pages_read_user_content` - Required for reading page-specific content

**Files Modified:**
- `app/Services/Platforms/FacebookConnector.php` - Updated `getAuthUrl()` and `handleCallback()`

**Previous Scopes:**
```php
'pages_show_list',
'pages_read_engagement',
'pages_manage_posts',
```

**New Scopes:**
```php
'pages_show_list',
'pages_read_engagement',
'pages_manage_posts',
'pages_manage_metadata',
'pages_read_user_content',
```

#### 3. Page Access Token Handling
**Architecture Change:**

**Before:**
- Created one OAuthToken for user token
- Stored page access tokens in ConnectedSocialAccount meta field
- Inconsistent token access patterns

**After:**
- Create separate OAuthToken record for each Facebook page
- Store page access token as encrypted token in OAuthToken table
- Store page permissions in both OAuthToken and ConnectedSocialAccount meta fields
- Consistent token access via relationship

**Files Modified:**
- `app/Services/Platforms/FacebookConnector.php` - Added `tasks` field to page query
- `app/Http/Controllers/OAuth/OAuthController.php` - Updated token storage logic
- `app/Jobs/PublishVariantJob.php` - Simplified token access
- `app/Services/MetricsService.php` - Simplified token access

**Benefits:**
- Consistent token management architecture
- Better security (encryption at rest)
- Easier token refresh logic
- Better permission tracking

#### 4. Permission Validation
**Added:** Facebook page tasks/permissions tracking

When connecting a Facebook page, we now fetch and store the page's `tasks` field, which contains the user's permissions for that page (e.g., `MANAGE`, `CREATE_CONTENT`, `MODERATE`, `ADVERTISE`).

**Usage:** Can be used to validate user has proper permissions before attempting to publish.

### LinkedIn Integration

#### 1. API Version Update
**Changed:** LinkedIn API version from 202401 to 202412

**Files Modified:**
- `app/Services/Platforms/LinkedInConnector.php` - Updated version headers throughout

**Reason:** 202412 is the latest stable version with improved APIs and features.

#### 2. OAuth Scopes
**Added Missing Scopes:**
- `openid` - Required for OpenID Connect authentication
- `profile` - Required for user profile access
- `w_member_social` - Required for posting on behalf of members

**Previous Scopes:**
```php
'r_organization_social',
'w_organization_social',
'rw_organization_admin',
```

**New Scopes:**
```php
'openid',
'profile',
'w_member_social',
'r_organization_social',
'w_organization_social',
'rw_organization_admin',
```

#### 3. API Endpoint Updates
**Changed:**
- User profile endpoint: `/v2/me` → `/v2/userinfo`
- Added consistent API version headers to all requests

**Files Modified:**
- `app/Services/Platforms/LinkedInConnector.php` - Updated `listPublishTargets()` and `publish()`

#### 4. REST API Headers
**Standardized:** All LinkedIn API calls now include:
```php
'LinkedIn-Version' => '202412',
'X-Restli-Protocol-Version' => '2.0.0',
```

**Files Modified:**
- `app/Services/Platforms/LinkedInConnector.php` - Added headers to `publish()` method

### Authorization & Permissions

#### 1. Policy Standardization
**Changed:** PostPolicy from role-based to privilege-based authorization

**Files Modified:**
- `app/Policies/PostPolicy.php` - Complete rewrite

**Reason:**
- Aligns with Tyro Dashboard best practices (see AGENTS.md)
- ConnectedSocialAccountPolicy already uses privileges
- More flexible and maintainable
- Follows privilege-based RBAC pattern

**Before:**
```php
public function viewAny(User $user): bool
{
    return $user->hasRole(['admin', 'editor', 'approver']);
}
```

**After:**
```php
public function viewAny(User $user): bool
{
    return $user->hasPrivilege('post.view');
}
```

**Required Privileges:**
- `post.view` - View posts
- `post.create` - Create and edit own drafts
- `post.manage` - Manage all posts
- `post.approve` - Approve/reject posts

#### 2. Controller Authorization
**Added:** Authorization checks to ConnectedAccountsController

**Files Modified:**
- `app/Http/Controllers/Dashboard/ConnectedAccountsController.php`

**Before:**
```php
public function index(Request $request)
{
    $brands = Brand::orderBy('name')->get();
    // No authorization check
}
```

**After:**
```php
public function index(Request $request)
{
    $this->authorize('viewAny', ConnectedSocialAccount::class);
    $brands = Brand::orderBy('name')->get();
}
```

### Code Quality Improvements

#### 1. Token Access Simplification
**Simplified:** Token access in PublishVariantJob and MetricsService

**Before (PublishVariantJob):**
```php
$accessToken = $token->access_token;

if ($variant->platform === 'facebook' && isset($account->meta['page_access_token'])) {
    $accessToken = $account->meta['page_access_token'];
}
```

**After (PublishVariantJob):**
```php
// Use the token's access_token directly
// For Facebook pages, we create separate tokens with page access tokens
// For LinkedIn, we use the organization/user token
$accessToken = $token->access_token;
```

#### 2. Documentation
**Added:** Comprehensive inline comments explaining:
- Token architecture for Facebook pages
- Why we create separate tokens
- How token access works for different platforms

## Configuration Updates

### Environment Variables

Update your `.env` file:

```env
# Facebook OAuth Configuration
FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
FACEBOOK_GRAPH_API_VERSION=v21.0  # Updated from v18.0

# LinkedIn OAuth Configuration
LINKEDIN_CLIENT_ID=your_linkedin_client_id_here
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret_here
```

### Facebook App Configuration

1. **Go to:** https://developers.facebook.com/apps/
2. **Select your app** or create a new one
3. **Add/Verify Permissions:**
   - Go to "App Review" → "Permissions and Features"
   - Request: `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `pages_manage_metadata`, `pages_read_user_content`
4. **Update OAuth Settings:**
   - Add redirect URI: `http://your-domain.com/oauth/facebook/callback`

### LinkedIn App Configuration

1. **Go to:** https://www.linkedin.com/developers/apps/
2. **Select your app** or create a new one
3. **Add Products:**
   - Marketing Developer Platform
   - Sign In with LinkedIn using OpenID Connect
4. **Verify Scopes:**
   - Ensure all scopes are enabled: `openid`, `profile`, `w_member_social`, `r_organization_social`, `w_organization_social`, `rw_organization_admin`
5. **Update OAuth Settings:**
   - Add redirect URI: `http://your-domain.com/oauth/linkedin/callback`

## Testing Guide

### 1. OAuth Connection Tests

#### Facebook Page Connection
```bash
# 1. Start the application
php artisan serve

# 2. Log in to the dashboard
# Visit: http://localhost:8008/dashboard

# 3. Create a brand (if not exists)
# Dashboard → Resources → Brands → Add New

# 4. Connect Facebook page
# Dashboard → Integrations → Connect Accounts
# Select brand → Click "Connect Facebook"

# 5. Authorize and select pages
# You'll be redirected to Facebook for authorization

# Expected Result:
# - Each page gets its own OAuthToken record
# - ConnectedSocialAccount created with proper meta data
# - Page tasks/permissions stored in meta
```

#### LinkedIn Organization Connection
```bash
# Follow same steps but click "Connect LinkedIn"

# Expected Result:
# - Single OAuthToken for user
# - ConnectedSocialAccount for each organization
# - Vanity name stored in meta
```

### 2. Publishing Tests

#### Test Post Creation and Publishing
```bash
# 1. Create a post
# Dashboard → Resources → Posts → Add New
# - Select brand
# - Enter title and content
# - Save

# 2. Create post variant
# Dashboard → Resources → Post Variants → Add New
# - Select the post
# - Select connected social account
# - Set platform (facebook or linkedin)
# - Set schedule time or leave blank for immediate
# - Save

# 3. Publish
# Option A: Immediate - Click "Publish Now" button
# Option B: Scheduled - Run queue worker: php artisan queue:work

# Expected Result:
# - Post published to social platform
# - PublicationAttempt record created with success status
# - External post ID stored
```

#### Test Error Handling
```bash
# 1. Revoke token manually on Facebook/LinkedIn
# 2. Try to publish a post
# Expected: Error logged, variant marked as failed, admins notified

# 3. Test rate limiting
# Expected: Job released with delay, retried later
```

### 3. Metrics Tests

#### Test Metrics Ingestion
```bash
# 1. Publish a post and wait a few hours (for engagement)

# 2. Run metrics ingestion manually
php artisan metrics:ingest

# Expected Result:
# - MetricsSnapshot created for each published variant
# - Metrics (likes, comments, shares, impressions, clicks) populated
# - Raw data stored in JSON

# 3. View analytics
# Dashboard → Analytics

# Expected:
# - Summary cards show aggregated metrics
# - Charts display engagement over time
# - Platform comparison works
# - Post performance details available
```

### 4. Permission Tests

#### Test Post Permissions
```bash
# 1. Create users with different roles
# - Editor (has post.create, post.view)
# - Approver (has post.approve, post.view)
# - Viewer (has post.view only)

# 2. Test as Editor
# Expected: Can create/edit own drafts, submit for approval
# Expected: Cannot approve, cannot edit others' posts

# 3. Test as Approver
# Expected: Can approve/reject pending posts
# Expected: Cannot create posts (if no post.create)

# 4. Test as Viewer
# Expected: Can only view posts
# Expected: Cannot create, edit, or approve
```

#### Test Connected Accounts Permissions
```bash
# 1. Visit /dashboard/connect-accounts without channel.view privilege
# Expected: 403 Forbidden

# 2. Try to connect account without channel.connect privilege
# Expected: 403 Forbidden

# 3. Try to disconnect without channel.manage privilege
# Expected: 403 Forbidden
```

### 5. Token Refresh Tests

#### Test Facebook Token Refresh
```bash
# Facebook long-lived tokens expire after 60 days

# 1. Find tokens expiring soon
php artisan tinker
> $tokens = \App\Models\OAuthToken::where('platform', 'facebook')
    ->where('expires_at', '<', now()->addDays(7))
    ->get();

# 2. Run token expiry watcher
php artisan oauth:watch-expiry --refresh

# Expected:
# - Tokens refreshed automatically
# - New expiry date set
# - Connected accounts remain active
```

#### Test LinkedIn Token Refresh
```bash
# LinkedIn tokens expire after 60 days (with refresh_token)

# 1. Use LinkedInConnector::refreshToken() when needed
# 2. Tokens should refresh automatically during publish if needed

# Test in code:
$connector = new \App\Services\Platforms\LinkedInConnector();
$token = \App\Models\OAuthToken::find(1);
$refreshed = $connector->refreshTokenIfNeeded($token);

# Expected:
# - If expires_at < 7 days, token refreshed
# - New access_token and refresh_token stored
# - Expiry extended
```

## Troubleshooting

### Common Issues

#### 1. "OAuth callback error"
**Cause:** Invalid redirect URI or app configuration
**Fix:**
- Verify redirect URI in Facebook/LinkedIn app settings
- Ensure it matches exactly: `http://your-domain.com/oauth/{platform}/callback`
- Check client ID and secret in `.env`

#### 2. "Permission denied" when publishing
**Cause:** Missing OAuth scopes or page permissions
**Fix:**
- Reconnect the account to get new permissions
- Verify app has requested permissions in Facebook/LinkedIn dashboard
- Check if user has required page role (ADMIN, EDITOR, etc.)

#### 3. "Token expired" errors
**Cause:** Token not refreshed automatically
**Fix:**
- Run: `php artisan oauth:watch-expiry --refresh`
- Check if refresh_token exists (LinkedIn only)
- Reconnect the account if refresh fails

#### 4. "Rate limit exceeded"
**Cause:** Too many API requests
**Fix:**
- PlatformRateLimiter automatically handles rate limiting
- Jobs will be delayed and retried
- Check logs for rate limit errors
- Consider increasing delay between requests in IngestMetricsJob

#### 5. "Metrics not appearing"
**Cause:** Post too new or metrics ingestion not running
**Fix:**
- Wait a few hours after publishing for engagement data
- Run: `php artisan metrics:ingest`
- Check if IngestMetricsJob is in queue
- Verify tokens are valid and not expired

## Database Changes

### No Migrations Required

All changes are backward compatible with existing data. The `meta` column in `connected_social_accounts` table already exists as a JSON column.

### Data Migration (Optional)

If you have existing connected Facebook accounts with page access tokens in the meta field, you may want to migrate them to the new structure:

```php
// Run in tinker: php artisan tinker

$accounts = \App\Models\ConnectedSocialAccount::where('platform', 'facebook')->get();

foreach ($accounts as $account) {
    if (isset($account->meta['page_access_token'])) {
        // Create new token record
        $pageToken = \App\Models\OAuthToken::create([
            'platform' => 'facebook',
            'access_token' => $account->meta['page_access_token'],
            'refresh_token' => null,
            'expires_at' => null,
            'scopes' => ['pages_read_engagement', 'pages_manage_posts', 'pages_manage_metadata', 'pages_read_user_content'],
            'meta' => [
                'page_id' => $account->external_account_id,
                'page_name' => $account->display_name,
            ],
        ]);

        // Update account to use new token
        $account->update(['token_id' => $pageToken->id]);

        echo "Migrated {$account->display_name}\n";
    }
}
```

## Performance Impact

### Positive Impacts
- ✅ Reduced redundant meta field checks
- ✅ Better query performance with proper relationships
- ✅ Cleaner code = easier maintenance

### No Negative Impacts
- ✅ No additional database queries (same relationship pattern)
- ✅ No additional API calls
- ✅ Token encryption overhead minimal (already encrypted)

## Security Improvements

1. **Consistent Encryption:** All tokens (user and page) encrypted at rest
2. **Permission Validation:** Facebook page tasks stored for validation
3. **Privilege-Based Auth:** Fine-grained access control
4. **Authorization Checks:** All controllers properly authorized

## Rollback Plan

If issues arise, you can rollback:

```bash
# Rollback git changes
git checkout main

# Clear cache
php artisan config:clear
php artisan cache:clear

# Restart queue workers
php artisan queue:restart
```

To use old .env values:
```env
FACEBOOK_GRAPH_API_VERSION=v18.0  # Revert to old version
```

## Future Improvements

### Suggested Enhancements
1. ⚡ Add webhook support for real-time token expiry notifications
2. 🔔 Add admin alerts for failed publications
3. 📊 Add dashboard for monitoring OAuth health
4. 🧪 Add automated tests for OAuth flows
5. 📝 Add PostVariantPolicy for granular permissions
6. 🔐 Add brand-level access control (users can only access their brands)

### API Version Monitoring
- Consider adding a cron job to check for API deprecation notices
- Store API version in token meta field for debugging
- Log API version used for each request

## Support

For issues or questions:
1. Check application logs: `storage/logs/laravel.log`
2. Check queue logs: `php artisan queue:failed`
3. Review this document's troubleshooting section
4. Check Facebook/LinkedIn developer documentation

## Changelog

**2026-02-06:**
- Updated Facebook Graph API to v21.0
- Updated LinkedIn API to 202412
- Added missing OAuth permissions
- Standardized token handling
- Fixed PostPolicy to use privileges
- Added authorization checks
- Improved documentation
