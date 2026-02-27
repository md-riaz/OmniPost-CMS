# Security Baseline

## Immediate Rules
- Never commit `.env`.
- Never ship default credentials.
- Rotate exposed keys/tokens immediately.

## First-Boot Security Flow
1. Copy `.env.example` to `.env`.
2. Run `php artisan key:generate`.
3. Open the app and complete `/setup` to create first admin.
4. Configure OAuth secrets per environment.

## Rotation Checklist
- `APP_KEY`
- OAuth client secrets (Facebook/LinkedIn)
- Any SMTP/API secrets in `.env`
- Existing session cookies (invalidate sessions after key/security changes)

## CI Gate Requirements
Each PR must pass:
- `composer install`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan test`
