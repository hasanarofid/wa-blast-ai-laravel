<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// 

Route::middleware(['auth', 'role:master'])->group(function () {
    Route::get('/master', function () {
        return view('master.dashboard');
    });
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    });
});

Route::middleware(['auth', 'role:cs'])->group(function () {
    Route::get('/cs', function () {
        return view('cs.dashboard');
    });
});

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer', function () {
        return view('customer.dashboard');
    });
});

Route::get('/redirect-role', function () {
    $user = auth()->user();
    return match ($user->role) {
        'master' => redirect('/master'),
        'admin' => redirect('/admin'),
        'cs' => redirect('/cs'),
        'customer' => redirect('/customer'),
        default => abort(403),
    };
});

// 
