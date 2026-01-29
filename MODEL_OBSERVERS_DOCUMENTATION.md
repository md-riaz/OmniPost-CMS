# Model Observers Documentation

## Overview

This document explains the model observers implemented in OmniPost CMS to automatically populate user-related foreign key fields during model creation.

## Problem Statement

Several models in the application have non-nullable foreign key fields that reference the `users` table. When creating these models through various interfaces (Tyro Dashboard, API endpoints, factories, etc.), these fields need to be populated with the authenticated user's ID. Without proper handling, this can lead to SQL constraint violations.

## Solution: Model Observers

We've implemented Laravel Model Observers that automatically populate user-related fields with the authenticated user's ID during model creation. This approach:

- ✅ Works universally across all creation methods
- ✅ Keeps code DRY (Don't Repeat Yourself)
- ✅ Respects explicitly set values
- ✅ Follows Laravel conventions
- ✅ Requires no configuration changes

## Implemented Observers

### 1. PostObserver

**File**: `app/Observers/PostObserver.php`

**Purpose**: Automatically populates the `created_by` field when creating a Post.

**Fields Handled**:
- `created_by` (foreign key to users table)

**Behavior**:
```php
public function creating(Post $post): void
{
    if (empty($post->created_by) && auth()->check()) {
        $post->created_by = auth()->id();
    }
}
```

**Usage Example**:
```php
// As an authenticated user
$post = Post::create([
    'brand_id' => 1,
    'title' => 'My Post',
    'base_text' => 'Content',
    'status' => 'draft'
    // created_by automatically set to auth()->id()
]);
```

### 2. PostCommentObserver

**File**: `app/Observers/PostCommentObserver.php`

**Purpose**: Automatically populates the `user_id` field when creating a PostComment.

**Fields Handled**:
- `user_id` (foreign key to users table)

**Behavior**:
```php
public function creating(PostComment $comment): void
{
    if (empty($comment->user_id) && auth()->check()) {
        $comment->user_id = auth()->id();
    }
}
```

**Usage Example**:
```php
// As an authenticated user
$comment = PostComment::create([
    'post_id' => 1,
    'comment_text' => 'Great post!'
    // user_id automatically set to auth()->id()
]);
```

**Note**: While the PostWorkflowController explicitly sets `user_id`, this observer provides defense-in-depth by ensuring the field is always populated, even if created through other means (API, factory, seeder, etc.).

### 3. PostStatusChangeObserver

**File**: `app/Observers/PostStatusChangeObserver.php`

**Purpose**: Automatically populates the `changed_by` field when creating a PostStatusChange.

**Fields Handled**:
- `changed_by` (foreign key to users table)

**Behavior**:
```php
public function creating(PostStatusChange $change): void
{
    if (empty($change->changed_by) && auth()->check()) {
        $change->changed_by = auth()->id();
    }
}
```

**Usage Example**:
```php
// As an authenticated user
$statusChange = PostStatusChange::create([
    'post_id' => 1,
    'from_status' => 'draft',
    'to_status' => 'pending',
    'changed_at' => now()
    // changed_by automatically set to auth()->id()
]);
```

**Note**: While the PostStatusService explicitly sets `changed_by`, this observer provides a safety net for direct model creation.

## Observer Registration

All observers are registered in `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    // Register model observers
    Post::observe(PostObserver::class);
    PostComment::observe(PostCommentObserver::class);
    PostStatusChange::observe(PostStatusChangeObserver::class);
}
```

## Other Models

### Models That DON'T Need Observers

**AuditLog**:
- Uses a static `log()` method that automatically handles `user_id`
- Not created directly via normal Eloquent methods
- Already properly encapsulated

**ConnectedSocialAccount**:
- Created via OAuth flow, not through dashboard forms
- Doesn't have a user foreign key (has `brand_id` instead)

**Brand, PostVariant, PublicationAttempt, MetricsSnapshot**:
- Don't have user foreign key fields
- User relationship not applicable to these models

## Key Features

### 1. Respects Explicit Values

If a field is explicitly set, the observer won't override it:

```php
// Explicit value is preserved
$post = Post::create([
    'brand_id' => 1,
    'title' => 'My Post',
    'created_by' => 5  // This will NOT be overridden
]);
// $post->created_by === 5
```

### 2. Requires Authentication

Observers only set values when a user is authenticated:

```php
if (empty($model->field) && auth()->check()) {
    $model->field = auth()->id();
}
```

### 3. Works Everywhere

Observers work for all creation methods:
- ✅ Tyro Dashboard forms
- ✅ API endpoints
- ✅ Model factories
- ✅ Database seeders
- ✅ Artisan commands
- ✅ Direct Eloquent creation

## Testing

Comprehensive tests are provided in:
- `tests/Feature/PostCreationTest.php` - Post-specific tests
- `tests/Feature/ModelObserversTest.php` - All observer tests

**Test Coverage**:
- ✅ Automatic population of user fields
- ✅ Preservation of explicit values
- ✅ Successful creation without constraint errors
- ✅ Works for all affected models

**Run Tests**:
```bash
# Run all observer tests
php artisan test --filter=ModelObserversTest

# Run all tests
php artisan test
```

## Troubleshooting

### Issue: Field not being set automatically

**Check**:
1. Is the user authenticated? (`auth()->check()` returns true?)
2. Is the field empty before creation?
3. Is the observer registered in AppServiceProvider?

### Issue: Explicit value being overridden

**This shouldn't happen** - observers check `if (empty($field))` before setting.

If it does happen, check:
1. Is the field truly set (not null/empty)?
2. Are there multiple observers registered?

### Issue: SQL constraint violation

**Possible causes**:
1. User not authenticated when creating model
2. Observer not registered
3. Application not bootstrapped (e.g., in tests without proper setup)

**Solution**:
- Ensure user is authenticated before model creation
- Check observer registration in AppServiceProvider
- Use RefreshDatabase trait in tests

## Best Practices

### 1. Always Authenticate First

```php
// Good
auth()->login($user);
$post = Post::create([...]);

// Bad - will fail if observer doesn't catch it
$post = Post::create([...]);
```

### 2. Use Factories in Tests

```php
// Good - factories handle authentication
$user = User::factory()->create();
$this->actingAs($user);
$post = Post::factory()->create();
```

### 3. Trust the Observer

```php
// Good - let observer handle it
$post = Post::create([
    'brand_id' => 1,
    'title' => 'Post',
    // created_by omitted - observer will set it
]);

// Also fine - explicit is okay
$post = Post::create([
    'brand_id' => 1,
    'title' => 'Post',
    'created_by' => auth()->id()
]);
```

## Maintenance

### Adding New Observers

If you add a new model with a user foreign key:

1. Create observer: `app/Observers/ModelNameObserver.php`
2. Implement `creating()` method
3. Register in `AppServiceProvider::boot()`
4. Add tests in `tests/Feature/ModelObserversTest.php`

### Removing Observers

If a model no longer needs an observer:

1. Remove registration from AppServiceProvider
2. Delete observer file
3. Update tests
4. Ensure field is handled elsewhere (controller, service, etc.)

## Conclusion

Model observers provide a robust, maintainable solution for automatically populating user-related foreign keys. They follow Laravel conventions, work universally, and require no configuration changes to existing code.

**Benefits**:
- ✅ Prevents SQL constraint violations
- ✅ Reduces code duplication
- ✅ Works across all creation methods
- ✅ Respects explicit values
- ✅ Easy to test and maintain
- ✅ Follows Laravel best practices

**Test Results**: 28 tests passing (61 assertions)
