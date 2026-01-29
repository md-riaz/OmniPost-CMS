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

## 🚀 Deployment Options

### Docker One-Click Deployment (Recommended)

OmniPost CMS is containerized and compatible with Docker management tools like **Portio** or **Portainer**.

1. **Deploy using Docker Compose**:
   - Create a new Stack in your Docker manager.
   - Paste the contents of [`docker-compose.yml`](docker-compose.yml).
   - Update the environment variables if needed (e.g., `APP_KEY`, `APP_URL`).
   - Deploy!

   **Services included**:
   - `app`: PHP 8.3 FPM application container
   - `nginx`: Web server
   - `db`: MariaDB 10.11 (optimized for performance)
   - `redis`: Cache and Queue manager

2. **Standard Docker Run**:
   ```bash
   docker-compose up -d --build
   ```

### Standard Server Deployment

If you prefer a traditional server setup (e.g., Ubuntu/Nginx/PHP), follow these steps:

1. **Server Requirements**:
   - PHP 8.3+ with Extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `pdo_sqlite`, `tokenizer`, `xml`.
   - Composer 2.x
   - Node.js 20+ (for asset compilation)
   - Supervisor (for queue workers)

2. **Setup Steps**:
   ```bash
   # Clone Repo
   git clone https://github.com/md-riaz/OmniPost-CMS.git /var/www/omnipost
   cd /var/www/omnipost

   # Install Dependencies
   composer install --optimize-autoloader --no-dev
   npm ci && npm run build

   # Permissions
   chown -R www-data:www-data /var/www/omnipost
   chmod -R 775 storage bootstrap/cache
   ```

3. **Queue Configuration**:
   Use Supervisor to run the queue worker:
   ```ini
   [program:omnipost-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/omnipost/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/omnipost/worker.log
   ```

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

---

## 🚀 Quick Start: Publishing Your First Post

Follow this step-by-step flow to set up a new brand and start publishing to Facebook/LinkedIn:

### Step 1: Create a Brand

```
Dashboard → Resources → Brands → Add New
```

1. Enter a **Brand Name** (e.g., "My Company")
2. Set a unique **Slug** (e.g., "my-company")
3. Select your **Timezone** for scheduling
4. Set **Status** to Active
5. Click **Save**

### Step 2: Connect Social Accounts

```
Dashboard → Integrations → Connect Accounts
```

1. Select your brand from the dropdown
2. Click **Connect Facebook** or **Connect LinkedIn**
3. Authorize on the social platform
4. Select the Pages/Organizations to connect
5. You'll be redirected back with connected accounts listed

### Step 3: Create a Post

```
Dashboard → Resources → Posts → Add New
```

1. Select your **Brand**
2. Enter an **Internal Title** (for organization)
3. Write your **Base Content** (the main post text)
4. Optionally add a **Target URL** and **UTM Template**
5. Click **Save** (creates post as Draft)

### Step 4: Create Post Variants

```
Dashboard → Resources → Post Variants → Add New
```

1. Select the **Post** you just created
2. Choose the **Social Account** (Facebook Page or LinkedIn Org)
3. Set **Platform** (facebook or linkedin)
4. Optionally add **Custom Text** override for this platform
5. Set a **Schedule Time** for publishing
6. Click **Save**

### Step 5: Approval Workflow (if enabled)

1. Go to your Post and click **Submit for Approval**
2. An approver reviews and clicks **Approve**
3. Post status changes to "Approved"

### Step 6: Publish

- **Scheduled**: Posts publish automatically at the scheduled time via queue worker
- **Immediate**: Click **Publish Now** on the Post Variant to publish immediately

### Visual Flow

```
┌─────────────┐     ┌─────────────────┐     ┌──────────────┐
│  1. Create  │────▶│ 2. Connect      │────▶│ 3. Create    │
│    Brand    │     │    Accounts     │     │    Post      │
└─────────────┘     └─────────────────┘     └──────┬───────┘
                                                   │
┌─────────────┐     ┌─────────────────┐     ┌──────▼───────┐
│ 6. Publish  │◀────│ 5. Approve      │◀────│ 4. Create    │
│   (Queue)   │     │    Post         │     │   Variants   │
└─────────────┘     └─────────────────┘     └──────────────┘
```

> **Tip**: Run `php artisan queue:work` to process scheduled publications automatically.

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
   - Create a new brand with a name and timezone

3. **Connect Social Accounts (via UI)**
   - Click **"Connect Accounts"** in the sidebar under "Integrations"
   - Or visit: `http://localhost:8000/dashboard/connect-accounts`
   - Select your brand from the dropdown
   - Click **"Connect Facebook"** or **"Connect LinkedIn"**
   - You'll be redirected to authorize your account
   - After authorization, connected accounts appear in the table below

4. **Alternative: Direct URL Method**
   - Facebook: `http://localhost:8000/oauth/facebook/redirect?brand_id=1`
   - LinkedIn: `http://localhost:8000/oauth/linkedin/redirect?brand_id=1`
   - Replace `1` with your actual brand ID

5. **View Connected Accounts**
   - The Connect Accounts page shows all your connected accounts
   - Or visit the "Connected Accounts" resource for a table view

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
- Go to **Connect Accounts** page in the sidebar
- Click the "Disconnect" button next to the account
- The status will be updated to "revoked"

**Reconnecting an Expired Account**:
- Go to **Connect Accounts** page
- Click the "Reconnect" button next to the expired account
- This will initiate a new OAuth flow


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
