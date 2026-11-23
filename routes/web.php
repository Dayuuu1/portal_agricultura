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
use App\Http\Controllers\MesaPartes\AccountController;
use App\Http\Controllers\MesaPartes\LoginController;
use App\Http\Controllers\MesaPartes\MesaPartesController;
use App\Http\Controllers\MesaPartes\NotificationController;
use App\Http\Controllers\MesaPartes\PasswordResetController;
use App\Http\Controllers\MesaPartes\RegisterController;
use App\Http\Controllers\MesaPartes\SubmissionController;
use App\Http\Controllers\MesaPartes\SubmissionFileController;
use App\Http\Controllers\MesaPartes\VerificationController;
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
Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::get('/libro-de-reclamaciones', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/libro-de-reclamaciones', [ComplaintController::class, 'store'])->middleware('throttle:complaints')->name('complaints.store');
Route::get('/libro-de-reclamaciones/constancia/{complaint:code}', [ComplaintController::class, 'receipt'])->middleware('signed')->name('complaints.receipt');

/*
| Mesa de Partes Virtual (Ley N° 31170). Citizens use their own guard («citizen»), separate from the panel users.
*/
Route::prefix('mesa-de-partes')->name('mesa-partes.')->group(function () {
    Route::get('/', [MesaPartesController::class, 'index'])->name('index');
    Route::get('/consulta', [MesaPartesController::class, 'lookup'])->middleware('throttle:mesa-partes-lookup')->name('lookup');

    Route::middleware('guest:citizen')->group(function () {
        Route::middleware('mesa-partes.enabled')->group(function () {
            Route::get('/registro', [RegisterController::class, 'create'])->name('register');
            Route::post('/registro', [RegisterController::class, 'store'])->middleware('throttle:mesa-partes-register')->name('register.store');
        });
        Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
        Route::post('/ingresar', [LoginController::class, 'store'])->middleware('throttle:mesa-partes-login')->name('login.store');
        Route::get('/recuperar-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/recuperar-contrasena', [PasswordResetController::class, 'email'])->middleware('throttle:mesa-partes-password')->name('password.email');
        Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])->middleware('throttle:mesa-partes-password')->name('password.update');
    });

    // «auth.session» ends the other sessions of a citizen as soon as the password changes.
    Route::middleware(['auth:citizen', 'auth.session', 'mesa-partes.active'])->group(function () {
        Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/verificar-correo', [VerificationController::class, 'notice'])->name('verification.notice');
        Route::get('/verificar-correo/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
        Route::post('/verificar-correo/reenviar', [VerificationController::class, 'send'])->middleware('throttle:3,1')->name('verification.send');

        Route::prefix('mi-cuenta')->middleware('verified:mesa-partes.verification.notice')->group(function () {
            Route::get('/', [AccountController::class, 'dashboard'])->name('account');
            Route::middleware('mesa-partes.enabled')->group(function () {
                Route::get('/tramites/nuevo', [SubmissionController::class, 'create'])->name('submissions.create');
                Route::post('/tramites', [SubmissionController::class, 'store'])->middleware('throttle:10,60')->name('submissions.store');
            });
            Route::get('/tramites/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
            Route::get('/tramites/{submission}/cargo', [SubmissionController::class, 'receipt'])->name('submissions.receipt');
            Route::post('/tramites/{submission}/subsanar', [SubmissionController::class, 'cure'])->middleware('throttle:10,60')->name('submissions.cure');
            Route::get('/archivos/{file}', [SubmissionFileController::class, 'citizen'])->name('files.download');
            Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/notificaciones/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
            Route::get('/perfil', [AccountController::class, 'edit'])->name('profile');
            Route::put('/perfil', [AccountController::class, 'update'])->name('profile.update');
            Route::put('/perfil/contrasena', [AccountController::class, 'password'])->name('profile.password');
        });
    });
});

// Files of the expedientes for the panel staff (private disk).
Route::get('/admin/archivos/mesa-de-partes/{file}', [SubmissionFileController::class, 'staff'])->middleware('auth')->name('admin.mesa-partes.file');

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
