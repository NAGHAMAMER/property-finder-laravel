<?php

namespace Tests\Feature;

use App\Models\Messages;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NormalUserParityAndChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_conversation_is_scoped_by_property_and_supports_replies(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $buyer = User::factory()->create(['role' => 'user']);
        $property = Property::query()->create([
            'user_id' => $owner->id,
            'type' => 'بيت',
            'location' => 'دمشق',
            'price' => 100000,
            'badroom' => 3,
            'bathroom' => 2,
            'area' => 150,
            'status' => 'متاح',
            'approval_status' => 'approved',
        ]);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/properties/{$property->id}/messages", [
            'content' => 'هل العقار متاح؟',
        ])->assertCreated()
            ->assertJsonPath('thread.property_id', $property->id)
            ->assertJsonPath('thread.other_user_id', $owner->id);

        Sanctum::actingAs($owner);
        $this->getJson("/api/chats/{$property->id}/{$buyer->id}")
            ->assertOk()
            ->assertJsonPath('data.property.id', $property->id)
            ->assertJsonPath('data.other_user.id', $buyer->id)
            ->assertJsonPath('data.messages.0.content', 'هل العقار متاح؟');

        $this->postJson("/api/chats/{$property->id}/{$buyer->id}/messages", [
            'content' => 'نعم، ما زال متاحًا.',
        ])->assertCreated();

        $this->assertDatabaseCount('messages', 2);
        $this->assertTrue(Messages::query()->where('property_id', $property->id)->exists());
    }

    public function test_chat_with_same_user_on_two_properties_creates_two_threads(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $buyer = User::factory()->create(['role' => 'user']);

        $properties = collect([1, 2])->map(fn (int $index) => Property::query()->create([
            'user_id' => $owner->id,
            'type' => $index === 1 ? 'بيت' : 'شقة',
            'location' => 'دمشق',
            'price' => 100000 + $index,
            'badroom' => 2,
            'bathroom' => 1,
            'area' => 100,
            'status' => 'متاح',
            'approval_status' => 'approved',
        ]));

        Sanctum::actingAs($buyer);
        foreach ($properties as $property) {
            $this->postJson("/api/properties/{$property->id}/messages", [
                'content' => "رسالة للعقار {$property->id}",
            ])->assertCreated();
        }

        $this->getJson('/api/chats')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_password_reset_api_returns_a_new_mobile_token(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'normal@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/forgot-password/reset', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk()
            ->assertJsonStructure(['message', 'token', 'token_type', 'user']);

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_property_specific_chat_blade_page_renders(): void
    {
        $this->get('/app/chats/62/7')
            ->assertOk()
            ->assertSee('جارٍ تحميل الرسائل')
            ->assertSee('/chats/${chatPropertyId}/${chatOtherUserId}', false);
    }
}
