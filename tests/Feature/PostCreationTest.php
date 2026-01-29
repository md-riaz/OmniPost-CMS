<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that created_by is automatically set when creating a post as authenticated user.
     */
    public function test_created_by_is_automatically_set_on_post_creation(): void
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a brand
        $brand = Brand::factory()->create();

        // Create a post without explicitly setting created_by
        $post = Post::create([
            'brand_id' => $brand->id,
            'title' => 'Test Post',
            'base_text' => 'Test content',
            'status' => 'draft',
        ]);

        // Assert that created_by was automatically set to the authenticated user
        $this->assertNotNull($post->created_by);
        $this->assertEquals($user->id, $post->created_by);
    }

    /**
     * Test that explicitly set created_by is not overridden.
     */
    public function test_explicitly_set_created_by_is_not_overridden(): void
    {
        // Create two users
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        
        // Authenticate as current user
        $this->actingAs($currentUser);

        // Create a brand
        $brand = Brand::factory()->create();

        // Create a post with explicit created_by
        $post = Post::create([
            'brand_id' => $brand->id,
            'title' => 'Test Post',
            'base_text' => 'Test content',
            'status' => 'draft',
            'created_by' => $otherUser->id,
        ]);

        // Assert that created_by was not overridden
        $this->assertEquals($otherUser->id, $post->created_by);
        $this->assertNotEquals($currentUser->id, $post->created_by);
    }

    /**
     * Test that post can be created successfully without SQL constraint error.
     */
    public function test_post_creation_succeeds_without_constraint_error(): void
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a brand
        $brand = Brand::factory()->create();

        // This should not throw a SQL constraint error
        $post = Post::create([
            'brand_id' => $brand->id,
            'title' => 'Test Post via Dashboard',
            'base_text' => 'Content created through Tyro Dashboard',
            'status' => 'draft',
        ]);

        // Assert post was created successfully
        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->exists);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Test Post via Dashboard',
            'created_by' => $user->id,
        ]);
    }
}
