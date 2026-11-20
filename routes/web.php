<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AgrarianInformationController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstitutionalController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TransparencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/noticias', [PostController::class, 'index'])->name('posts.index');
Route::get('/noticias/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/documentos', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documentos/{document:slug}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documentos/{document:slug}/descargar', [DocumentController::class, 'download'])->name('documents.download');

Route::get('/convocatorias', [JobPostingController::class, 'index'])->name('job-postings.index');
Route::get('/convocatorias/{jobPosting:slug}', [JobPostingController::class, 'show'])->name('job-postings.show');

Route::prefix('institucional')->name('institutional.')->controller(InstitutionalController::class)->group(function () {
    Route::get('/nosotros', 'about')->name('about');
    Route::get('/organigrama', 'organizationChart')->name('organization-chart');
    Route::get('/directorio', 'staffDirectory')->name('directory');
    Route::get('/unidades/{unit:slug}', 'unit')->name('unit');
});

Route::get('/agencias-agrarias', [AgencyController::class, 'index'])->name('agencies.index');
Route::get('/agencias-agrarias/{agency:slug}', [AgencyController::class, 'show'])->name('agencies.show');

Route::get('/informacion-agraria', AgrarianInformationController::class)->name('agrarian-information');
Route::get('/transparencia', TransparencyController::class)->name('transparency');

Route::get('/contacto', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/libro-de-reclamaciones', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/libro-de-reclamaciones', [ComplaintController::class, 'store'])->middleware('throttle:5,1')->name('complaints.store');
Route::get('/libro-de-reclamaciones/constancia/{complaint:code}', [ComplaintController::class, 'receipt'])->middleware('signed')->name('complaints.receipt');

Route::get('/buscar', SearchController::class)->name('search');
Route::get('/p/{page:slug}', PageController::class)->name('pages.show');

// URLs del portal WordPress anterior.
Route::permanentRedirect('/contactenos', '/contacto');
Route::permanentRedirect('/buzon', '/contacto');
Route::permanentRedirect('/libro-de-reclamaciones-2', '/libro-de-reclamaciones');
Route::permanentRedirect('/nosotros', '/informacion-agraria');
Route::permanentRedirect('/organigrama', '/institucional/organigrama');
Route::permanentRedirect('/category/noticias', '/noticias');
Route::permanentRedirect('/boletines-2019', '/informacion-agraria');
Route::permanentRedirect('/boletines-2022', '/informacion-agraria');
Route::permanentRedirect('/precios-de-los-mercados-minoristas-de-madre-de-dios', '/informacion-agraria');

Route::fallback(LegacyRedirectController::class);
