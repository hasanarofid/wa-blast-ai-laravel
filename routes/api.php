<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\ServiceController;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\WAWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// WhatsApp Webhook Routes
Route::prefix('whatsapp')->group(function () {
    Route::get('/webhook', [WhatsAppWebhookController::class, 'verify']);
    Route::post('/webhook', [WhatsAppWebhookController::class, 'webhook']);
});

// Delivery API Routes
Route::prefix('v1')->group(function () {
    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::put('/{order}', [OrderController::class, 'update']);
        Route::post('/calculate-price', [OrderController::class, 'calculatePrice']);
        Route::get('/statistics', [OrderController::class, 'statistics']);
    });

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{customer}', [CustomerController::class, 'show']);
        Route::put('/{customer}', [CustomerController::class, 'update']);
        Route::get('/{customer}/orders', [CustomerController::class, 'orders']);
        Route::get('/{customer}/transactions', [CustomerController::class, 'transactions']);
    });

    // Partners
    Route::prefix('partners')->group(function () {
        Route::get('/', [PartnerController::class, 'index']);
        Route::post('/', [PartnerController::class, 'store']);
        Route::get('/{partner}', [PartnerController::class, 'show']);
        Route::put('/{partner}', [PartnerController::class, 'update']);
        Route::get('/{partner}/orders', [PartnerController::class, 'orders']);
        Route::get('/{partner}/transactions', [PartnerController::class, 'transactions']);
        Route::post('/{partner}/go-online', [PartnerController::class, 'goOnline']);
        Route::post('/{partner}/go-offline', [PartnerController::class, 'goOffline']);
    });

    // Services
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::get('/{service}', [ServiceController::class, 'show']);
        Route::put('/{service}', [ServiceController::class, 'update']);
    });
});

// Legacy routes
Route::post('/whatsapp/webhook', [WebhookController::class, 'handle']);
