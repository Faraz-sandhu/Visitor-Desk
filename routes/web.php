<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('companies', CompanyController::class)->only('store');
    Route::resource('employees', EmployeeController::class)->only(['index','create','store']);
    Route::resource('visits', VisitController::class)->only(['index','create','store','show']);
    Route::get('/visits/{visit}/photo', [VisitController::class,'photo'])->name('visits.photo');
    Route::middleware('can:admin')->group(function () {
        Route::resource('companies', CompanyController::class)->except(['show','store']);
        Route::resource('employees', EmployeeController::class)->only(['edit','update','destroy']);
        Route::resource('visits', VisitController::class)->only(['edit','update','destroy']);
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show','destroy']);
        Route::get('/reports', [\App\Http\Controllers\ReportController::class,'index'])->name('reports.index');
        Route::get('/reports/download', [\App\Http\Controllers\ReportController::class,'download'])->name('reports.download');
    });
    Route::patch('/visits/{visit}/checkout', [VisitController::class, 'checkout'])->name('visits.checkout');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
