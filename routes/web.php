<?php

use App\Http\Controllers\PortalController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::get('/collections', [PortalController::class, 'collections'])->name('collections.index');
Route::get('/collections/{collection:slug}', [PortalController::class, 'show'])->name('collections.show');
Route::get('/collections/{collection:slug}/search', [SearchController::class, 'index'])->name('collections.search');
Route::get('/collections/{collection:slug}/facets/{facet}', [SearchController::class, 'facet'])->name('collections.facets.show');
Route::get('/collections/{collection:slug}/map-data', [SearchController::class, 'mapData'])->name('collections.map-data');
Route::get('/specimens/{specimen:occurrence_id}', [SearchController::class, 'show'])->name('specimens.show');
Route::get('/workflow', [PortalController::class, 'workflow'])->name('workflow');
Route::get('/status', [PortalController::class, 'status'])->name('status');
