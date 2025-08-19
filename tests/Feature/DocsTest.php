<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_docs_route_is_accessible_to_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/docs');

        $response->assertStatus(200);
    }

    public function test_docs_route_redirects_unauthenticated_users()
    {
        $response = $this->get('/docs');

        $response->assertRedirect('/login');
    }

    public function test_api_docs_redirects_to_docs()
    {
        $response = $this->get('/api/docs');

        $response->assertRedirect('/docs');
    }
}
