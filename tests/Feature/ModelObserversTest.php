<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostStatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelObserversTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Post created_by is automatically set.
     */
    public function test_post_created_by_is_automatically_set(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $brand = Brand::factory()->create();

        $post = Post::create([
            'brand_id' => $brand->id,
            'title' => 'Test Post',
            'base_text' => 'Test content',
            'status' => 'draft',
        ]);

        $this->assertEquals($user->id, $post->created_by);
    }

    /**
     * Test that PostComment user_id is automatically set.
     */
    public function test_post_comment_user_id_is_automatically_set(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $brand = Brand::factory()->create();
        $post = Post::factory()->create(['brand_id' => $brand->id, 'created_by' => $user->id]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'comment_text' => 'Test comment',
        ]);

        $this->assertEquals($user->id, $comment->user_id);
    }

    /**
     * Test that PostStatusChange changed_by is automatically set.
     */
    public function test_post_status_change_changed_by_is_automatically_set(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $brand = Brand::factory()->create();
        $post = Post::factory()->create(['brand_id' => $brand->id, 'created_by' => $user->id]);

        $statusChange = PostStatusChange::create([
            'post_id' => $post->id,
            'from_status' => 'draft',
            'to_status' => 'pending',
            'changed_at' => now(),
        ]);

        $this->assertEquals($user->id, $statusChange->changed_by);
    }

    /**
     * Test that explicit user values are not overridden.
     */
    public function test_explicit_user_values_are_not_overridden(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($currentUser);
        
        $brand = Brand::factory()->create();

        // Post with explicit created_by
        $post = Post::create([
            'brand_id' => $brand->id,
            'title' => 'Test Post',
            'base_text' => 'Test content',
            'status' => 'draft',
            'created_by' => $otherUser->id,
        ]);
        $this->assertEquals($otherUser->id, $post->created_by);

        // PostComment with explicit user_id
        $comment = PostComment::create([
            'post_id' => $post->id,
            'comment_text' => 'Test comment',
            'user_id' => $otherUser->id,
        ]);
        $this->assertEquals($otherUser->id, $comment->user_id);

        // PostStatusChange with explicit changed_by
        $statusChange = PostStatusChange::create([
            'post_id' => $post->id,
            'from_status' => 'draft',
            'to_status' => 'pending',
            'changed_by' => $otherUser->id,
            'changed_at' => now(),
        ]);
        $this->assertEquals($otherUser->id, $statusChange->changed_by);
    }

    /**
     * Test that models can be created successfully without constraint errors.
     */
    public function test_all_models_create_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $brand = Brand::factory()->create();

        // Create Post
        $post = Post::create([
            'brand_id' => $brand->id,
            'title' => 'Test Post',
            'base_text' => 'Content',
            'status' => 'draft',
        ]);
        $this->assertTrue($post->exists);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'created_by' => $user->id]);

        // Create PostComment
        $comment = PostComment::create([
            'post_id' => $post->id,
            'comment_text' => 'Test comment',
        ]);
        $this->assertTrue($comment->exists);
        $this->assertDatabaseHas('post_comments', ['id' => $comment->id, 'user_id' => $user->id]);

        // Create PostStatusChange
        $statusChange = PostStatusChange::create([
            'post_id' => $post->id,
            'from_status' => 'draft',
            'to_status' => 'pending',
            'changed_at' => now(),
        ]);
        $this->assertTrue($statusChange->exists);
        $this->assertDatabaseHas('post_status_changes', ['id' => $statusChange->id, 'changed_by' => $user->id]);
    }
}
