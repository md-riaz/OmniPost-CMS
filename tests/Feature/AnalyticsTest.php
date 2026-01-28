<?php

namespace Tests\Feature;

use App\Jobs\IngestMetricsJob;
use App\Models\Brand;
use App\Models\ConnectedSocialAccount;
use App\Models\MetricsSnapshot;
use App\Models\OAuthToken;
use App\Models\Post;
use App\Models\PostVariant;
use App\Models\PublicationAttempt;
use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
    }

    public function test_analytics_dashboard_loads()
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard.analytics.index'));

        $response->assertStatus(200);
        $response->assertSee('Analytics Dashboard');
    }

    public function test_analytics_export_generates_csv()
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard.analytics.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_metrics_snapshot_calculates_engagement_rate()
    {
        $brand = Brand::factory()->create();
        $post = Post::factory()->create(['brand_id' => $brand->id]);
        $variant = PostVariant::factory()->create(['post_id' => $post->id]);

        $snapshot = MetricsSnapshot::create([
            'post_variant_id' => $variant->id,
            'captured_at' => now(),
            'likes' => 10,
            'comments' => 5,
            'shares' => 3,
            'impressions' => 200,
            'clicks' => 15,
        ]);

        $this->assertEquals(9.0, $snapshot->engagement_rate);
        $this->assertEquals(7.5, $snapshot->click_through_rate);
    }

    public function test_metrics_service_gets_variants_to_ingest()
    {
        $brand = Brand::factory()->create();
        $token = OAuthToken::factory()->create(['platform' => 'facebook']);
        $account = ConnectedSocialAccount::factory()->create([
            'brand_id' => $brand->id,
            'platform' => 'facebook',
            'token_id' => $token->id,
        ]);
        
        $post = Post::factory()->create(['brand_id' => $brand->id]);
        $variant = PostVariant::factory()->create([
            'post_id' => $post->id,
            'platform' => 'facebook',
            'connected_social_account_id' => $account->id,
            'scheduled_at' => now()->subDays(5),
        ]);

        PublicationAttempt::create([
            'post_variant_id' => $variant->id,
            'attempt_no' => 1,
            'result' => 'success',
            'external_post_id' => '123456789',
            'queued_at' => now()->subDays(5),
            'started_at' => now()->subDays(5),
            'finished_at' => now()->subDays(5),
        ]);

        $service = app(MetricsService::class);
        $variants = $service->getVariantsToIngest(30);

        $this->assertCount(1, $variants);
        $this->assertEquals($variant->id, $variants->first()->id);
    }

    public function test_ingest_metrics_job_dispatches()
    {
        Queue::fake();

        IngestMetricsJob::dispatch(30);

        Queue::assertPushed(IngestMetricsJob::class);
    }

    public function test_post_performance_view_loads()
    {
        $brand = Brand::factory()->create();
        $post = Post::factory()->create(['brand_id' => $brand->id]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard.analytics.post-performance', $post));

        $response->assertStatus(200);
        $response->assertSee($post->title);
    }
}
