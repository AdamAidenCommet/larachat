<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_run_safe_commands()
    {
        $user = User::factory()->create();

        // Test pwd command
        $response = $this->actingAs($user)
            ->postJson('/api/run-command', [
                'command' => 'pwd',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'output',
                'success',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('output'));

        // Test echo command
        $response = $this->actingAs($user)
            ->postJson('/api/run-command', [
                'command' => 'echo "Hello World"',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'output' => 'Hello World',
                'success' => true,
            ]);
    }

    public function test_unauthenticated_user_cannot_run_command()
    {
        $response = $this->postJson('/api/run-command', [
            'command' => 'pwd',
        ]);

        $response->assertStatus(401);
    }

    public function test_any_command_can_be_executed()
    {
        $user = User::factory()->create();

        // Test various commands
        $response = $this->actingAs($user)
            ->postJson('/api/run-command', [
                'command' => 'echo "test"',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'output',
                'success',
            ]);
    }

    public function test_command_field_is_required()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/run-command', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['command']);
    }

    public function test_command_must_be_string()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/run-command', [
                'command' => 123,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['command']);
    }

    public function test_various_commands_work_correctly()
    {
        $user = User::factory()->create();

        // Test ls command
        $response = $this->actingAs($user)
            ->postJson('/api/run-command', [
                'command' => 'ls -la',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'output',
                'success',
            ]);

        // Test date command
        $response = $this->actingAs($user)
            ->postJson('/api/run-command', [
                'command' => 'date',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'output',
                'success',
            ]);
    }
}
