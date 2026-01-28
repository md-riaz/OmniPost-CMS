# Phase 6: Analytics + Reporting - Implementation Summary

## Overview
Phase 6 adds comprehensive analytics collection and reporting capabilities to OmniPost CMS, enabling teams to measure campaign performance across social media platforms.

## Implemented Features

### 1. Metrics Collection System

#### MetricsSnapshot Model
- Enhanced with computed properties:
  - `engagement_rate`: Calculates engagement as a percentage of impressions
  - `click_through_rate`: Calculates CTR as a percentage of impressions
  - `getTotalEngagement()`: Returns sum of likes, comments, and shares
- Stores metrics for each post variant:
  - likes, comments, shares
  - impressions, clicks
  - raw_metrics (JSON) for platform-specific data

#### Platform Connectors
**FacebookConnector::fetchMetrics()**
- Fetches insights from Facebook Graph API
- Metrics: post_impressions, post_engaged_users, post_clicks
- Also fetches post data for reactions, comments, shares
- Returns normalized metrics array

**LinkedInConnector::fetchMetrics()**
- Fetches statistics from LinkedIn Marketing API
- Uses organizationalEntityShareStatistics endpoint
- Metrics: likeCount, commentCount, shareCount, impressionCount, clickCount
- Handles organization URN extraction from share URN

#### MetricsService
Core business logic for metrics operations:
- `ingestMetricsForVariant()`: Fetches and stores metrics for a single variant
- `getVariantsToIngest()`: Returns published variants from last N days
- `getMetricsForPost()`: Returns metrics for a specific post
- `getAggregatedMetrics()`: Returns filtered metrics with brand/platform/date filters
- Handles token expiration and platform-specific logic

#### IngestMetricsJob
Scheduled job for automated metrics collection:
- Runs nightly at 2:00 AM
- Processes published variants from last 30 days
- Batches requests with 100ms delay (rate limit protection)
- Handles errors gracefully:
  - Rate limiting: pauses and retries in 1 hour
  - Token expiration: logs and skips
  - API errors: logs and continues
- Reports success/failure/skipped counts

### 2. Analytics Dashboard

#### Main Dashboard (`/dashboard/analytics`)
**Features:**
- Summary statistics cards:
  - Total posts published
  - Total engagement (likes + comments + shares)
  - Total impressions
  - Average engagement rate
- Filters:
  - Brand selection
  - Platform selection (Facebook/LinkedIn)
  - Date range (from/to)
- Charts:
  - **Engagement Over Time**: Line chart showing engagement, impressions, clicks
  - **Platform Comparison**: Doughnut chart showing engagement by platform
  - **Best Posting Times**: Bar chart showing engagement rate by hour of day
- Top performing posts table
- CSV export functionality
- Caching: Dashboard data cached for 1 hour

#### Post Performance View (`/dashboard/analytics/posts/{post}`)
**Features:**
- Performance cards for each platform variant:
  - Latest metrics (likes, comments, shares, impressions, clicks)
  - Engagement rate
  - Platform-specific account name
- Historical performance chart:
  - Line chart showing engagement growth over time
  - Separate lines for each platform variant
- Platform comparison table:
  - Total engagement per platform
  - Average engagement rate
  - Best performing day

### 3. Data Export

#### CSV Export
- Exports all metrics with current filters
- Columns included:
  - Date, Post ID, Post Title
  - Brand, Platform, Account
  - Likes, Comments, Shares
  - Impressions, Clicks
  - Engagement Rate, CTR
- Filename format: `metrics_export_YYYY-MM-DD_HHMMSS.csv`
- Streams response for memory efficiency

### 4. Artisan Command

**`php artisan metrics:ingest`**
- Manual metrics ingestion command
- Options:
  - `--days=30`: Number of days to look back (default: 30)
- Dispatches IngestMetricsJob to queue
- Useful for testing and ad-hoc data collection

### 5. Scheduled Tasks

Configured in `routes/console.php`:
```php
Schedule::job(new \App\Jobs\IngestMetricsJob(30))->dailyAt('02:00');
```

### 6. Testing

**AnalyticsTest** (6 tests, 12 assertions):
- ✅ Analytics dashboard loads
- ✅ Analytics export generates CSV
- ✅ Metrics snapshot calculates engagement rate
- ✅ Metrics service gets variants to ingest
- ✅ Ingest metrics job dispatches
- ✅ Post performance view loads

### 7. Model Factories

Created factories for testing:
- BrandFactory
- PostFactory
- PostVariantFactory
- OAuthTokenFactory
- ConnectedSocialAccountFactory

All models now include `HasFactory` trait.

## Technical Implementation Details

### Database Schema Changes
**Migration:** `add_meta_to_connected_social_accounts_table`
- Added `meta` JSON column to `connected_social_accounts` table
- Used to store platform-specific data (e.g., page_access_token for Facebook)

### Chart.js Integration
- Version: 4.4.0
- CDN: https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
- Chart types used:
  - Line charts (engagement over time, historical performance)
  - Doughnut chart (platform comparison)
  - Bar chart (best posting times)

### Error Handling & Rate Limiting

**Facebook API:**
- Rate limit: 200 calls/hour
- Error codes:
  - 190: Token expired
  - 613: Rate limited
- Handling: Log error, retry rate limits after 1 hour

**LinkedIn API:**
- Rate limit: 500 calls/day
- Error codes:
  - 401: Unauthorized/expired token
  - 429: Too many requests
- Handling: Log error, retry rate limits after 1 hour

### Caching Strategy
- Dashboard data cached for 1 hour
- Cache key format: `analytics_dashboard_{brandId}_{platform}_{dateFrom}_{dateTo}`
- Invalidation: Automatic expiry after 1 hour

### Performance Considerations
- Eager loading: `with(['connectedSocialAccount.oauthToken', 'publicationAttempts'])`
- Query optimization: Indexed columns used in filters
- Batch processing: 100ms delay between API requests
- Historical lookback: Limited to 90 days (configurable)

## Routes

```php
GET  /dashboard/analytics                     # Main dashboard
GET  /dashboard/analytics/posts/{post}        # Post performance view
GET  /dashboard/analytics/export              # CSV export
```

## Future Enhancements

### Not Yet Implemented:
1. **API Version Tracking**
   - Store API version used in token meta field
   - Log deprecation warnings
   - Allow admin configuration of API version

2. **Real-time Metrics**
   - Webhook integration for instant metrics updates
   - Live dashboard updates

3. **Advanced Analytics**
   - Sentiment analysis of comments
   - Demographic breakdowns
   - Competitor benchmarking

4. **Notifications**
   - Alert when post performance exceeds threshold
   - Warning when engagement drops
   - Daily/weekly email reports

5. **Predictive Analytics**
   - Best time to post recommendations
   - Content performance predictions
   - Audience growth forecasting

## Usage Examples

### Manual Metrics Ingestion
```bash
# Ingest metrics for last 30 days
php artisan metrics:ingest

# Ingest metrics for last 7 days
php artisan metrics:ingest --days=7
```

### Accessing Analytics
1. Navigate to `/dashboard/analytics`
2. Apply filters (brand, platform, date range)
3. View charts and top performing posts
4. Click on post to see detailed performance
5. Export data as CSV

### Programmatic Access
```php
use App\Services\MetricsService;

$service = app(MetricsService::class);

// Get metrics for a post
$metrics = $service->getMetricsForPost(
    postId: 1,
    dateFrom: '2024-01-01',
    dateTo: '2024-01-31'
);

// Get aggregated metrics
$metrics = $service->getAggregatedMetrics(
    brandId: 1,
    platform: 'facebook',
    dateFrom: '2024-01-01',
    dateTo: '2024-01-31'
);
```

## Security Considerations

1. **Token Security**
   - Access tokens encrypted at rest
   - Never exposed in logs or responses
   - Page access tokens stored in meta field

2. **Data Privacy**
   - No PII from comments stored
   - Only aggregate metrics collected
   - GDPR compliant data retention

3. **Authorization**
   - All routes protected by authentication
   - Role-based access control via Tyro Dashboard
   - Metrics only visible to authorized users

## Monitoring & Logging

All metrics operations logged:
- Successful ingestion
- API errors (with status codes)
- Rate limit hits
- Token expiration events
- Job failures

Log levels:
- INFO: Successful operations
- WARNING: Skipped variants, missing tokens
- ERROR: API failures, job failures

## Performance Metrics

Expected performance:
- Metrics ingestion: ~2-3 seconds per variant (with API delays)
- Dashboard load: <500ms (with caching)
- CSV export: Streams data, no memory limit issues
- Chart rendering: Client-side, minimal server load

## Conclusion

Phase 6 provides a complete analytics solution for OmniPost CMS. Teams can now:
- Track performance across all platforms
- Identify best-performing content
- Optimize posting schedules
- Export data for further analysis
- Make data-driven decisions

All core requirements from IMPLEMENTATION_STATUS.md have been implemented and tested.
