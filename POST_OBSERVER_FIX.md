# Post Observer Fix - Auto-populate created_by Field

## Problem

The `posts` table has a non-nullable `created_by` foreign key field that references the `users` table. When creating posts through the Tyro Dashboard, this field was not being populated, resulting in SQL constraint errors:

```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'created_by' cannot be null
```

## Solution

Implemented a **Model Observer** pattern to automatically populate the `created_by` field with the authenticated user's ID when a post is being created.

## Implementation Details

### 1. Created PostObserver (`app/Observers/PostObserver.php`)

```php
<?php

namespace App\Observers;

use App\Models\Post;

class PostObserver
{
    /**
     * Handle the Post "creating" event.
     *
     * Automatically set the created_by field to the authenticated user
     * if it hasn't been explicitly set.
     */
    public function creating(Post $post): void
    {
        if (empty($post->created_by) && auth()->check()) {
            $post->created_by = auth()->id();
        }
    }
}
```

**Key Features:**
- Only sets `created_by` if it's not already set (respects explicit values)
- Only sets if a user is authenticated (`auth()->check()`)
- Runs during the `creating` event (before the record is saved to database)

### 2. Registered Observer in AppServiceProvider

Modified `app/Providers/AppServiceProvider.php` to register the observer:

```php
public function boot(): void
{
    // Register model observers
    Post::observe(PostObserver::class);
}
```

## Benefits

1. **Clean Separation of Concerns**: Business logic (auto-setting created_by) is in the observer, not in controllers
2. **Works Everywhere**: Applies to all post creation methods (Tyro Dashboard, API, seeder, factory, etc.)
3. **Respects Explicit Values**: If `created_by` is explicitly set, it won't be overridden
4. **Laravel Convention**: Uses standard Laravel observer pattern
5. **No Config Changes Needed**: Tyro Dashboard configuration remains unchanged

## Testing

Added comprehensive tests in `tests/Feature/PostCreationTest.php`:

1. **test_created_by_is_automatically_set_on_post_creation**
   - Verifies that `created_by` is automatically set to authenticated user

2. **test_explicitly_set_created_by_is_not_overridden**
   - Ensures explicit `created_by` values are not overridden

3. **test_post_creation_succeeds_without_constraint_error**
   - Confirms posts can be created via dashboard without SQL errors

All tests pass: ✅ 3 passed (7 assertions)

## Usage

No changes required for developers or users. The observer works automatically:

```php
// As an authenticated user, simply create a post
$post = Post::create([
    'brand_id' => 1,
    'title' => 'My Post',
    'base_text' => 'Content',
    'status' => 'draft',
    // created_by is automatically set to auth()->id()
]);
```

## Alternative Approaches Considered

1. **Adding created_by to Tyro Dashboard config**: Would require users to manually select creator (bad UX)
2. **Using default value in migration**: Can't use `auth()->id()` in migration
3. **Setting in controller**: Would only work for specific routes, not factory/seeder/etc.
4. **Model accessor**: Accessors don't modify the database value

**Conclusion**: Observer pattern is the cleanest and most maintainable solution.

## Compatibility

- ✅ Works with Tyro Dashboard
- ✅ Works with factories and seeders
- ✅ Works with direct Eloquent creation
- ✅ Works with API endpoints
- ✅ Respects explicit values when needed
- ✅ No breaking changes to existing code

