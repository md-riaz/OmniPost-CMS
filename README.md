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

## 📸 Dashboard Preview

Experience the OmniPost CMS interface with these screenshots from the live application. All pages are fully functional and ready to use after installation.

### Login & Authentication

#### Login Page
![Login Page](https://github.com/user-attachments/assets/53debc51-61e5-4c1a-9d7d-46feadfd367a)

Clean, modern login interface with email and password authentication. Default credentials: `admin@omnipost.local` / `password123`

---

### Dashboard Home

#### Main Dashboard
![Dashboard Home](https://github.com/user-attachments/assets/23eff9b6-20dd-4754-ae20-ea43afa84372)

The dashboard home provides an overview of your system with:
- User statistics and counts
- Role distribution
- Quick access to recent users
- System-wide metrics at a glance

---

### User Management

#### My Profile
![My Profile](https://github.com/user-attachments/assets/23eff9b6-20dd-4754-ae20-ea43afa84372)

Manage your personal profile including:
- Name and email settings
- Password management
- Account information
- Role assignments

#### Users Management
![Users](https://github.com/user-attachments/assets/ce83a99d-0159-4259-9ffc-ea8ff11712fb)

Comprehensive user management with:
- Search and filter capabilities
- Role-based filtering
- User status management (Active/Suspended)
- Quick actions (Edit, Suspend)

---

### Role-Based Access Control (RBAC)

#### Roles Management
![Roles](https://github.com/user-attachments/assets/3289ea89-101e-48a5-b6ba-d55a58b9c878)

Manage user roles and permissions:
- 8 predefined roles (Administrator, Editor, Manager, Approver, etc.)
- User count per role
- Privilege assignment tracking
- Protected roles (cannot be deleted)

#### Privileges Management
![Privileges](https://github.com/user-attachments/assets/6324f64c-c819-46c0-b99f-0f77f0fd7114)

Granular permission management:
- 17 distinct privileges
- Brand, channel, post, calendar, and analytics permissions
- Role assignment tracking
- Search and pagination

---

### Content Management

#### Brands Resource
![Brands](https://github.com/user-attachments/assets/95474f0e-2a93-430c-94f7-cc521bf5de79)

Organize your content by brands/clients:
- Brand name and slug management
- Timezone configuration
- Active/inactive status
- CRUD operations

#### Posts Resource
![Posts](https://github.com/user-attachments/assets/a12bc24a-4ac1-4c3a-bf3e-5a632e6d6200)

Core content management:
- Internal title and base content
- Brand association
- Status tracking (Draft, Pending, Approved, etc.)
- Target URL and UTM template support
- Full CRUD capabilities

#### Post Variants Resource
![Post Variants](https://github.com/user-attachments/assets/05eea4d4-6bf5-4338-8863-46fc31c80fe5)

Platform-specific content variations:
- Create variants for each social platform
- Schedule publishing times
- Platform-specific text overrides
- Media customization per platform

---

### System Resources

#### Connected Accounts
![Connected Accounts](https://github.com/user-attachments/assets/a7af5bc9-0b48-41d2-8581-f40c06501fdd)

> *Note: This page requires OAuth configuration to function. See the OAuth Configuration section above.*

Manage connected social media accounts:
- Facebook Pages connections
- LinkedIn Organizations
- Token status monitoring
- Connect/disconnect functionality

#### Publication Attempts
![Publication Attempts](https://github.com/user-attachments/assets/fdaff4a7-2dd6-4c59-828d-bc5f19fc49a8)

> *Note: This page requires configured Tyro Dashboard resources. See the Tyro Dashboard configuration section.*

Track publishing history:
- Attempt logs and results
- Error debugging
- External post IDs
- Retry status

#### Metrics & Analytics
![Metrics](https://github.com/user-attachments/assets/802aad75-0593-486f-af48-f6487f1ccb15)

> *Note: This page requires configured Tyro Dashboard resources. See the Tyro Dashboard configuration section.*

Performance analytics:
- Likes, comments, shares
- Impressions and clicks
- Historical snapshots
- Platform-specific metrics

---

### UI Features

All dashboard pages feature:
- ✨ **Modern Design** - Clean, professional interface
- 🌙 **Dark Mode** - Toggle between light and dark themes
- 📱 **Responsive** - Works on desktop, tablet, and mobile
- 🔍 **Search & Filter** - Quick data access
- 🎯 **Breadcrumbs** - Easy navigation
- ⚡ **Fast Performance** - Optimized for speed
- 🔒 **Secure** - Role-based access control on all pages


## OAuth Configuration

### Setting Up Facebook OAuth

1. **Create a Facebook App**
   - Go to [Facebook Developers](https://developers.facebook.com/apps/)
   - Create a new app or select an existing one
   - Choose "Business" as the app type

2. **Configure OAuth Settings**
   - In your app dashboard, go to "Settings" → "Basic"
   - Copy your App ID and App Secret
   - Add `http://localhost:8000/oauth/facebook/callback` to "Valid OAuth Redirect URIs" under "Facebook Login" settings

3. **Request Required Permissions**
   - Go to "App Review" → "Permissions and Features"
   - Request these permissions:
     - `pages_show_list` - View your Pages
     - `pages_read_engagement` - Read your Pages data
     - `pages_manage_posts` - Publish and manage your Pages posts

4. **Update .env File**
   ```bash
   FACEBOOK_CLIENT_ID=your_actual_app_id
   FACEBOOK_CLIENT_SECRET=your_actual_app_secret
   FACEBOOK_GRAPH_API_VERSION=v18.0
   ```

### Setting Up LinkedIn OAuth

1. **Create a LinkedIn App**
   - Go to [LinkedIn Developers](https://www.linkedin.com/developers/apps/)
   - Create a new app
   - Fill in the required information

2. **Configure OAuth Settings**
   - In your app settings, go to the "Auth" tab
   - Copy your Client ID and Client Secret
   - Add `http://localhost:8000/oauth/linkedin/callback` to "Authorized redirect URLs"

3. **Request Required Permissions**
   - Go to the "Products" tab
   - Add these products:
     - Marketing Developer Platform (for posting to organizations)
     - Sign In with LinkedIn (for basic authentication)
   - You'll need these scopes:
     - `r_organization_social` - Read organization social media content
     - `w_organization_social` - Write organization social media content
     - `rw_organization_admin` - Administer organization pages

4. **Update .env File**
   ```bash
   LINKEDIN_CLIENT_ID=your_actual_client_id
   LINKEDIN_CLIENT_SECRET=your_actual_client_secret
   ```

### Connecting Social Accounts

Once you've configured the OAuth credentials:

1. **Log in to the dashboard**
   - Navigate to http://localhost:8000/dashboard

2. **Create a Brand** (if not exists)
   - Go to "Brands" resource
   - Create a new brand

3. **Connect Facebook Pages**
   - Visit: `http://localhost:8000/oauth/facebook/redirect?brand_id=1`
   - You'll be redirected to Facebook to authorize
   - Select the pages you want to connect
   - You'll be redirected back with connected accounts

4. **Connect LinkedIn Organizations**
   - Visit: `http://localhost:8000/oauth/linkedin/redirect?brand_id=1`
   - You'll be redirected to LinkedIn to authorize
   - Select the organizations you want to connect
   - You'll be redirected back with connected accounts

5. **View Connected Accounts**
   - Go to "Connected Accounts" resource in the dashboard
   - You'll see all your connected Facebook Pages and LinkedIn Organizations

### Managing OAuth Tokens

**Manual Token Expiry Check**:
```bash
php artisan oauth:watch-expiry
```

**Auto-refresh Expiring Tokens**:
```bash
php artisan oauth:watch-expiry --refresh
```

**Automatic Scheduled Refresh**:
The system automatically runs the token expiry watcher nightly. To enable scheduled tasks:
```bash
php artisan schedule:work
```

**Disconnecting an Account**:
- Go to the "Connected Accounts" resource
- Find the account you want to disconnect
- The status will be updated to "revoked"

**Reconnecting an Expired Account**:
- Use the reconnect URL: `/oauth/accounts/{account_id}/reconnect`
- This will initiate a new OAuth flow for that specific account


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

### ✅ Phase 3: OAuth Integration (Complete)
- Platform connector interface
- Facebook Pages OAuth integration
- LinkedIn Organizations OAuth integration
- Token management and auto-refresh
- Token expiry watcher command

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
