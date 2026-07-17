<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\UserNotificationCreated;
use App\Mail\PasswordResetCodeMail;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinalSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_normal_api_login_work_without_creating_an_admin_web_session(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Normal User',
            'email' => 'normal@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated()
            ->assertJsonPath('user.name', 'Normal User')
            ->assertJsonPath('user.role', 'user');

        $this->postJson('/api/login', [
            'email' => 'normal@example.com',
            'password' => 'Password123',
        ])->assertOk()
            ->assertJsonStructure(['user', 'token']);

        $this->assertGuest('web');
    }

    public function test_my_properties_endpoint_returns_only_the_authenticated_users_properties(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->createProperty($user, 'pending');
        $this->createProperty($other, 'approved');

        Sanctum::actingAs($user);

        $this->getJson('/api/my-properties')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_property_creation_can_save_the_precise_map_location_atomically(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $this->post('/api/add-property', [
            'type' => 'شقة',
            'location' => 'دمشق - المزة',
            'price' => 100000,
            'badroom' => 2,
            'bathroom' => 1,
            'area' => 120,
            'status' => 'متاح',
            'latitude' => 33.5138000,
            'longitude' => 36.2765000,
            'documents' => [UploadedFile::fake()->create('proof.pdf', 50, 'application/pdf')],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.detailed_locations.latitude', '33.5138000')
            ->assertJsonPath('data.detailed_locations.longitude', '36.2765000');

        $this->assertDatabaseHas('detailed_locations', [
            'latitude' => 33.5138000,
            'longitude' => 36.2765000,
        ]);
    }

    public function test_authenticated_mobile_api_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        Sanctum::actingAs($user);

        $this->postJson('/api/change-password', [
            'current_password' => 'OldPassword123',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_normal_user_can_reset_password_by_email_code_through_the_api(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'mobile@example.com',
            'role' => 'user',
            'password' => 'OldPassword123',
        ]);

        $this->postJson('/api/forgot-password/send-code', [
            'email' => $user->email,
        ])->assertOk();

        $code = null;
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code, $user) {
            $code = $mail->code;
            return $mail->hasTo($user->email);
        });

        $this->assertNotNull($code);

        $this->postJson('/api/forgot-password/reset', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'RecoveredPassword123',
            'password_confirmation' => 'RecoveredPassword123',
        ])->assertOk();

        $this->assertTrue(Hash::check('RecoveredPassword123', $user->fresh()->password));
    }

    public function test_admin_login_replaces_any_existing_normal_web_session(): void
    {
        $normal = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'AdminPassword123',
        ]);

        $this->actingAs($normal, 'web');

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'AdminPassword123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_admin_can_reset_password_by_email_code_on_the_web(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-reset@example.com',
            'password' => 'OldAdminPassword123',
        ]);

        $this->post('/admin/forgot-password', [
            'email' => $admin->email,
        ])->assertRedirect(route('admin.password.reset.form', ['email' => $admin->email]));

        $code = null;
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code, $admin) {
            $code = $mail->code;
            return $mail->hasTo($admin->email);
        });

        $this->post('/admin/reset-password', [
            'email' => $admin->email,
            'code' => $code,
            'password' => 'NewAdminPassword123',
            'password_confirmation' => 'NewAdminPassword123',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('NewAdminPassword123', $admin->fresh()->password));
        $this->assertGuest('web');
    }

    public function test_message_is_saved_notified_and_emits_immediate_realtime_events(): void
    {
        Event::fake([MessageSent::class, UserNotificationCreated::class]);

        $owner = User::factory()->create();
        $sender = User::factory()->create();
        $property = $this->createProperty($owner, 'approved');
        Sanctum::actingAs($sender);

        $this->postJson("/api/send_message/{$property->id}", [
            'content' => 'مرحبًا، هل العقار ما زال متاحًا؟',
        ])->assertCreated();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $owner->id,
            'property_id' => $property->id,
        ]);
        $this->assertSame(1, $owner->notifications()->count());
        Event::assertDispatched(MessageSent::class);
        Event::assertDispatched(UserNotificationCreated::class);
    }

    public function test_broadcast_private_channel_can_be_authorized_with_a_sanctum_bearer_user(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
            'broadcasting.connections.pusher.options.cluster' => 'mt1',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-App.Models.User.{$user->id}",
        ], ['Accept' => 'application/json'])->assertOk();

        $other = User::factory()->create();
        $this->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-App.Models.User.{$other->id}",
        ], ['Accept' => 'application/json'])->assertForbidden();
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
