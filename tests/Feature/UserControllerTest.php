<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que l'accès aux routes d'administration exige d'être authentifié.
     */
    public function test_user_routes_require_authentication(): void
    {
        $this->getJson('/api/users')->assertStatus(401);
        $this->postJson('/api/users', [])->assertStatus(401);
        $this->deleteJson('/api/users/1')->assertStatus(401);
    }

    /**
     * Test de la liste des utilisateurs.
     */
    public function test_can_list_users_when_authenticated(): void
    {
        $admin = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['email' => $admin->email])
            ->assertJsonFragment(['email' => $otherUser->email]);
    }

    /**
     * Test de validation lors de la création d'un utilisateur.
     */
    public function test_create_user_validates_required_fields(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test de validation sur l'unicité de l'email.
     */
    public function test_cannot_create_user_with_duplicate_email(): void
    {
        $admin = User::factory()->create();
        $existingUser = User::factory()->create(['email' => 'duplicate@example.com']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test de la création réussie d'un utilisateur.
     */
    public function test_can_create_user_successfully(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Nouveau Compte',
            'email' => 'newadmin@ayomide.com',
            'password' => 'securePassword123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.name', 'Nouveau Compte')
            ->assertJsonPath('user.email', 'newadmin@ayomide.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@ayomide.com'
        ]);
    }

    /**
     * Test qu'un administrateur ne peut pas supprimer son propre compte.
     */
    public function test_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$admin->id}");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Vous ne pouvez pas supprimer votre propre compte administrateur.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id
        ]);
    }

    /**
     * Test de la suppression réussie d'un autre utilisateur.
     */
    public function test_can_delete_other_user(): void
    {
        $admin = User::factory()->create();
        $userToDelete = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Utilisateur supprimé avec succès');

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id
        ]);
    }
}
