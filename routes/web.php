<?php

use App\Http\Controllers\Web\PublicPortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Public Cultural Portal
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicPortalController::class, 'home'])->name('home');

Route::get('/issues', [PublicPortalController::class, 'issues'])->name('issues.index');
Route::get('/issues/{slug}', [PublicPortalController::class, 'issueShow'])->name('issues.show');

Route::get('/articles/{slug}', [PublicPortalController::class, 'articleShow'])->name('articles.show');

Route::get('/artists', [PublicPortalController::class, 'artists'])->name('artists.index');
Route::get('/artists/{slug}', [PublicPortalController::class, 'artistShow'])->name('artists.show');

Route::get('/gallery', [PublicPortalController::class, 'gallery'])->name('gallery.index');

// Quick search endpoint for header search overlay
Route::get('/api/search', [PublicPortalController::class, 'search'])->name('api.search');

// Cultural Tracks Aliases (Matches AppLayout navigation items)
Route::get('/tracks/{category}', function (string $category) {
    return redirect()->route('home', ['#track-'.$category]);
})->name('tracks.show');
