<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoSecurityRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_video_upload_endpoint_is_not_exposed(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->post('/api/upload-video');

        $response->assertNotFound();
    }

    public function test_video_stream_route_requires_authentication(): void
    {
        $response = $this->get('/api/video-stream/invalid-token');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_with_invalid_token_gets_forbidden(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->get('/api/video-stream/invalid-token');

        $response->assertForbidden();
    }
}
