<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DetailedLocations;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Normal-user API only
|--------------------------------------------------------------------------
| Admin operations intentionally remain Blade/Web only.
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password/send-code', [PasswordResetController::class, 'sendUserCode'])
    ->middleware('throttle:5,1');
Route::post('/forgot-password/reset', [PasswordResetController::class, 'resetUserPassword'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    /* Account */
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [PasswordResetController::class, 'changeAuthenticatedPassword'])
        ->middleware('throttle:10,1');

    /* Clean REST-style property endpoints for mobile/Postman. */
    Route::get('/properties', [PropertyController::class, 'all_property']);
    Route::post('/properties', [PropertyController::class, 'add_property']);
    Route::get('/properties/mine', [PropertyController::class, 'myProperties']);
    Route::post('/properties/nearby', [DetailedLocations::class, 'nearby_properties']);
    Route::get('/properties/{id}', [PropertyController::class, 'show_Property']);
    Route::post('/properties/{id}/update', [PropertyController::class, 'edit_property']);
    Route::delete('/properties/{id}', [PropertyController::class, 'delete_Property']);
    Route::patch('/properties/{id}/status', [PropertyController::class, 'edit_property_status']);
    Route::post('/properties/{id}/images', [PropertyController::class, 'add_property_images']);
    Route::delete('/property-images/{id}', [PropertyController::class, 'delete_property_image']);
    Route::get('/properties/{property}/documents/{document}/download', [PropertyController::class, 'downloadDocument']);
    Route::post('/properties/{property_id}/location', [DetailedLocations::class, 'add_detailed_locations']);
    Route::delete('/properties/{property_id}/location', [DetailedLocations::class, 'delet_detailed_locations']);

    /* Ratings */
    Route::get('/properties/{property}/ratings', [RatingController::class, 'index']);
    Route::post('/properties/{property}/ratings', [RatingController::class, 'store']);
    Route::delete('/properties/{property}/ratings', [RatingController::class, 'destroy']);

    /* Favorites */
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/properties/{property}/favorite', [FavoriteController::class, 'store']);
    Route::delete('/properties/{property}/favorite', [FavoriteController::class, 'destroy']);

    /* Property-specific conversations only. */
    Route::post('/properties/{property_id}/messages', [MessageController::class, 'send_message']);
    Route::get('/chats', [MessageController::class, 'show_chats']);
    Route::get('/chats/{property_id}/{other_user_id}', [MessageController::class, 'showPropertyChat']);
    Route::post('/chats/{property_id}/{other_user_id}/messages', [MessageController::class, 'reply']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);

    /* Search and notifications */
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/notifications', [NotificationController::class, 'all_notifications']);
    Route::get('/notifications/live', [NotificationController::class, 'live']);

    /*
    |--------------------------------------------------------------------------
    | Legacy aliases retained for the existing web/mobile client
    |--------------------------------------------------------------------------
    */
    Route::post('/add-property', [PropertyController::class, 'add_property']);
    Route::get('/all_property', [PropertyController::class, 'all_property']);
    Route::get('/my-properties', [PropertyController::class, 'myProperties']);
    Route::get('/show_Property/{id}', [PropertyController::class, 'show_Property']);
    Route::get('/show_My_Property/{id}', [PropertyController::class, 'show_My_Property']);
    Route::post('/show_My_Property/show_Property/edit_property/{id}', [PropertyController::class, 'edit_property']);
    Route::delete('/show_My_Property/show_Property/delete_Property/{id}', [PropertyController::class, 'delete_Property']);
    Route::post('/show_My_Property/show_Property/edit_property_status/{id}', [PropertyController::class, 'edit_property_status']);
    Route::post('/show_My_Property/show_Property/{id}/add_property_images', [PropertyController::class, 'add_property_images']);
    Route::delete('/show_My_Property/show_Property/delete_property_image/{id}', [PropertyController::class, 'delete_property_image']);
    Route::post('/show_My_Property/show_Property/{property_id}/add_detailed_locations', [DetailedLocations::class, 'add_detailed_locations']);
    Route::delete('/show_My_Property/show_Property/{property_id}/delet_detailed_locations', [DetailedLocations::class, 'delet_detailed_locations']);
    Route::post('/nearby-properties', [DetailedLocations::class, 'nearby_properties']);

    Route::post('/send_message/{property_id}', [MessageController::class, 'send_message']);
    Route::get('/show_chats', [MessageController::class, 'show_chats']);
    Route::get('/show_one_chat/{property_id}/{other_user_id}', [MessageController::class, 'showPropertyChat']);
    Route::post('/show_one_chat/{property_id}/{other_user_id}/messages', [MessageController::class, 'reply']);
    Route::get('/show_one_chat/{id}', [MessageController::class, 'show_one_chat']);

    Route::get('/all_notifications', [NotificationController::class, 'all_notifications']);
});
