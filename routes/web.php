<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// 一般ユーザー用お問い合わせフロー
Route::get('/', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contact.confirm');
Route::post('/contacts', [ContactController::class, 'store'])->name('contact.store');
Route::get('/thanks', [ContactController::class, 'thanks'])->name('contact.thanks');

// 認証済み（ログイン後）の管理者だけがアクセスできるルートグループ
Route::middleware(['auth'])->prefix('admin')->group(function () {
    // 管理画面一覧（検索含む）
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    // お問い合わせ詳細ページ
    Route::get('/contacts/{contact}', [AdminController::class, 'show'])->name('admin.show');
    // お問い合わせ削除
    Route::delete('/contacts/{contact}', [AdminController::class, 'destroy'])->name('admin.destroy');
    // タグマスタ管理用ルート
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});

// CSVエクスポート
Route::get('/contacts/export', [ContactController::class, 'export'])
    ->middleware(['auth'])
    ->name('contacts.export');
