<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_add_list_and_remove_an_approved_property_from_favorites(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $property = $this->createProperty($owner, 'approved');

        Sanctum::actingAs($user);

        $this->postJson("/api/properties/{$property->id}/favorite")
            ->assertCreated()
            ->assertJsonPath('data.is_favorite', true);

        $this->postJson("/api/properties/{$property->id}/favorite")
            ->assertOk();

        $this->assertSame(1, Favorite::query()->count());

        $this->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $property->id)
            ->assertJsonPath('data.0.is_favorite', true);

        $this->getJson("/api/show_Property/{$property->id}")
            ->assertOk()
            ->assertJsonPath('data.is_favorite', true);

        $this->deleteJson("/api/properties/{$property->id}/favorite")
            ->assertOk()
            ->assertJsonPath('is_favorite', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_pending_property_cannot_be_added_to_favorites(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $property = $this->createProperty($owner, 'pending');

        Sanctum::actingAs($user);

        $this->postJson("/api/properties/{$property->id}/favorite")
            ->assertStatus(422);
    }

    public function test_admin_cannot_use_regular_user_favorites(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $property = $this->createProperty($owner, 'approved');

        Sanctum::actingAs($admin);

        $this->postJson("/api/properties/{$property->id}/favorite")
            ->assertForbidden();
    }

    private function createProperty(User $owner, string $approvalStatus): Property
    {
        return Property::create([
            'type' => 'شقة',
            'location' => 'دمشق',
            'price' => 100000,
            'badroom' => 2,
            'bathroom' => 1,
            'area' => 100,
            'status' => 'متاح',
            'user_id' => $owner->id,
            'approval_status' => $approvalStatus,
        ]);
    }
}
