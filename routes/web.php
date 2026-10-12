<?php

use App\Http\Controllers\AdminFileController;
use App\Http\Controllers\CareersController;
use App\Http\Controllers\OfferSigningController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

// Product landing page for the demo.
Route::view('/', 'public.landing')->name('home');

// Public careers site.
Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::get('/careers/talent-pool', [CareersController::class, 'talentPool'])->name('careers.talent-pool');
Route::post('/careers/talent-pool', [CareersController::class, 'joinTalentPool'])->middleware('throttle:10,1')->name('careers.talent-pool.store');
Route::get('/careers/thank-you', [CareersController::class, 'thanks'])->name('careers.thanks');
Route::get('/careers/{job:slug}', [CareersController::class, 'show'])->name('careers.show');
Route::get('/careers/{job:slug}/apply', [CareersController::class, 'apply'])->name('careers.apply');
Route::post('/careers/{job:slug}/apply', [CareersController::class, 'store'])->middleware('throttle:10,1')->name('careers.store');
Route::get('/jobs.xml', [CareersController::class, 'feed'])->name('careers.feed');

// Offer e-signature (secure token link).
Route::get('/offers/{token}', [OfferSigningController::class, 'show'])->name('offers.show');
Route::post('/offers/{token}/sign', [OfferSigningController::class, 'sign'])->name('offers.sign');
Route::post('/offers/{token}/decline', [OfferSigningController::class, 'decline'])->name('offers.decline');

// New-hire onboarding portal (secure token link).
Route::get('/portal/{token}', [PortalController::class, 'show'])->name('portal.show');
Route::post('/portal/{token}/tasks/{task}', [PortalController::class, 'complete'])->name('portal.complete');

// Files and exports for signed-in staff.
Route::middleware('auth')->group(function () {
    Route::get('/files/resumes/{candidate}', [AdminFileController::class, 'resume'])->name('resumes.download');
    Route::get('/files/onboarding/{task}', [AdminFileController::class, 'onboardingFile'])->name('portal.file');
    Route::get('/files/interviews/{interview}.ics', [AdminFileController::class, 'ics'])->name('interviews.ics');
    Route::get('/files/reports/applications.csv', [AdminFileController::class, 'export'])->name('reports.export');
});
