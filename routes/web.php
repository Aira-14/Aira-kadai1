<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

// 一般ユーザー用お問い合わせフロー
Route::get('/', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contact.confirm');
Route::post('/contacts', [ContactController::class, 'store'])->name('contact.store');
Route::get('/thanks', [ContactController::class, 'thanks'])->name('contact.thanks');
