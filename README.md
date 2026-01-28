# OmniPost CMS

> **One dashboard. Many platforms. Zero chaos.**

Unified Content & Campaign Management System for Digital Marketing Teams

## About OmniPost CMS

OmniPost CMS is a Laravel-based content management system built with Tyro Dashboard that enables digital marketing teams to manage multi-platform social media campaigns from a single interface. The system supports scheduling, approval workflows, publishing, and analytics for Facebook Pages and LinkedIn Organizations.

### Key Features

- 🏢 **Brand Management** - Organize content by brands/clients with timezone support
- 🔗 **Multi-Platform Integration** - Connect Facebook Pages and LinkedIn Organizations
- ✍️ **Post Management** - Create once, publish everywhere with platform-specific variants
- 📅 **Scheduling** - Schedule posts across multiple platforms
- ✅ **Approval Workflows** - Built-in approval system for content review
- 📊 **Analytics** - Track post performance with historical metrics
- 🔐 **RBAC** - Role-based access control (Admin, Manager, Editor)
- 🔒 **Secure** - OAuth token encryption and secure credential storage

## Technology Stack

- **Backend**: Laravel 12
- **Admin Dashboard**: Tyro Dashboard
- **Authentication**: Tyro Login with RBAC
- **Database**: SQLite (ready to use, no setup needed)
- **Queue**: Database-backed queue system
- **PHP**: 8.3+

## Quick Start

### Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- SQLite (included in PHP by default)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/md-riaz/OmniPost-CMS.git
   cd OmniPost-CMS
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **The database is already configured!** ✨
   - SQLite database is included and pre-configured
   - .env file is tracked in the repository for easy setup
   - All migrations are already run
   - Default superuser is created

4. **Start the development server**
   ```bash
   php artisan serve
   ```

5. **Access the dashboard**
   - URL: http://localhost:8000/dashboard
   - Email: `admin@omnipost.local`
   - Password: `password123`

That's it! No complex setup, no configuration files to create. Just install and run! 🚀

## Default Login Credentials

- **Email**: `admin@omnipost.local`
- **Password**: `password123`

⚠️ **Important**: Change the default password after first login in production!

## Project Structure

### Domain Models

```
app/Models/
├── Brand.php                      # Brand/client entity
├── ConnectedSocialAccount.php     # Connected social media accounts
├── OAuthToken.php                 # Encrypted OAuth credentials
├── Post.php                       # Base content post
├── PostVariant.php                # Platform-specific variations
├── PublicationAttempt.php         # Publishing attempt logs
└── MetricsSnapshot.php            # Performance metrics
```

### Database Schema

```
brands
├── id, name, slug, timezone, status
├── relationships: connectedSocialAccounts, posts

oauth_tokens
├── id, platform, access_token (encrypted), refresh_token (encrypted)
├── expires_at, scopes, meta

connected_social_accounts
├── id, brand_id, platform, external_account_id
├── display_name, token_id, status

posts
├── id, brand_id, created_by, status, title
├── base_text, base_media, target_url, utm_template
├── approved_by, approved_at

post_variants
├── id, post_id, platform, connected_social_account_id
├── text_override, media_override, scheduled_at, status

publication_attempts
├── id, post_variant_id, attempt_no
├── queued_at, started_at, finished_at, result
├── external_post_id, error_code, error_message

metrics_snapshots
├── id, post_variant_id, captured_at
├── likes, comments, shares, impressions, clicks
```

## User Roles & Permissions

### Admin
- Full access to all features
- User and role management
- Brand and channel management
- Can approve, publish, and view analytics

### Manager
- View brands and posts
- Approve posts
- View calendar and analytics
- No user/role management

### Editor
- Create and edit posts
- View brands and channels
- Submit posts for approval
- View calendar

## Dashboard Resources

The following resources are available in the Tyro Dashboard:

1. **Brands** (`/dashboard/resources/brands`)
   - Create and manage brands/clients
   - Set timezone for scheduling

2. **Connected Accounts** (`/dashboard/resources/connected-social-accounts`)
   - View connected social media accounts
   - Monitor connection status

3. **Posts** (`/dashboard/resources/posts`)
   - Create base content
   - Manage approval workflow

4. **Post Variants** (`/dashboard/resources/post-variants`)
   - Platform-specific content variations
   - Schedule publishing times

5. **Publication Attempts** (`/dashboard/resources/publication-attempts`)
   - View publishing history
   - Debug failed attempts

6. **Metrics** (`/dashboard/resources/metrics`)
   - View performance analytics
   - Historical data snapshots

## Development Phases

### ✅ Phase 1: Foundation (Complete)
- Laravel 12 with Tyro Dashboard
- SQLite database configuration
- RBAC setup
- Superuser creation

### ✅ Phase 2: Domain Model (Complete)
- Database migrations
- Eloquent models with relationships
- Tyro Dashboard resources
- RBAC privileges

### 🚧 Phase 3: OAuth Integration (Upcoming)
- Facebook Pages connector
- LinkedIn Organizations connector
- Token management

### 📋 Phase 4: Publishing Engine (Planned)
- Job queue system
- Platform adapters
- Retry logic

### 📋 Phase 5: Workflow (Planned)
- Approval system
- Calendar view
- Notifications

### 📋 Phase 6: Analytics (Planned)
- Metrics ingestion
- Performance dashboard
- CSV export

### 📋 Phase 7: Production Hardening (Planned)
- Rate limiting
- Security enhancements
- Observability

## Running Tests

```bash
php artisan test
```

## Queue Worker

For scheduled publishing and background jobs:

```bash
php artisan queue:work
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

- OAuth tokens are encrypted at rest using Laravel's encryption
- RBAC ensures proper access control
- All user inputs are validated and sanitized

## License

The OmniPost CMS is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Credits

Built with:
- [Laravel 12](https://laravel.com)
- [Tyro Dashboard](https://github.com/hasinhayder/tyro-dashboard)
- [Tyro RBAC](https://github.com/hasinhayder/tyro)
