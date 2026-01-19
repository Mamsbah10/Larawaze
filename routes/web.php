<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
// Inclure les routes API (stateless) sous le préfixe /api

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\LeaderboardController;
// Accueil
Route::get('/', function () {
    return redirect('/login');
});

// Auth
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// TomTom proxy and test endpoints moved to routes/api.php (stateless middleware)

// Routes protégées
Route::middleware(['ensure.auth'])->group(function () {

    // Fallback pour /icons/police.png : servir police.jpg si le png manque
    Route::get('/icons/police.png', function () {
        $path = public_path('icons/police.jpg');
        if (file_exists($path)) {
            return response()->file($path);
        }
        abort(404);
    });

Route::get('/map', [HomeController::class, 'map'])
    ->name('map')
    ->middleware('ensure.auth');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events/{event}/vote', [VoteController::class, 'vote'])->name('events.vote');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('/notifications', function() {
    return \App\Models\Notification::orderBy('id','desc')->get();});

    // Search history endpoints (per-user)
    Route::get('/search-history', [\App\Http\Controllers\SearchHistoryController::class, 'index'])->name('search.history.index');
    Route::post('/search-history', [\App\Http\Controllers\SearchHistoryController::class, 'store'])->name('search.history.store');
    Route::delete('/search-history', [\App\Http\Controllers\SearchHistoryController::class, 'clear'])->name('search.history.clear');
    // Delete single history entry
    Route::delete('/search-history/{id}', [\App\Http\Controllers\SearchHistoryController::class, 'destroy'])->name('search.history.destroy');

    // Favorite addresses (persistées côté serveur)
    Route::get('/favorites', [\App\Http\Controllers\FavoriteAddressController::class, 'index'])->name('favorites.index');
    Route::post('/favorites', [\App\Http\Controllers\FavoriteAddressController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{id}', [\App\Http\Controllers\FavoriteAddressController::class, 'destroy'])->name('favorites.destroy');

    // Favoris et historique supprimés — routes retirées

});
