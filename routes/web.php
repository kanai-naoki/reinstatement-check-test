<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
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
    return view('contact.index');
});

// routes/web.php

Route::middleware('auth')->group(function () {
    // 仮の管理者ページ（動作確認用）
    Route::get('/admin', function () {
        return '
            <h2>管理者画面へログイン成功しました！</h2>
            <form action="/logout" method="POST">
            '.csrf_field().'
                <button type="submit">ログアウト</button>
            </form>
    ';
    })->name('admin.index');
});
/*
// 認証（ログイン）が必要な管理者専用グループ
Route::middleware('auth')->group(function () {

    // 1. 管理画面一覧
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    // 2. お問い合わせ詳細・削除
    Route::get('/admin/contacts/{contact}', [AdminController::class, 'show'])->name('admin.show');
    Route::delete('/admin/contacts/{contact}', [AdminController::class, 'destroy'])->name('admin.destroy');

    // 3. タグ関連（追加・編集画面・更新・削除）
    Route::post('/admin/tags', [TagController::class, 'store'])->name('admin.tags.store');
    Route::get('/admin/tags/{tag}/edit', [TagController::class, 'edit'])->name('admin.tags.edit');
    Route::put('/admin/tags/{tag}', [TagController::class, 'update'])->name('admin.tags.update');
    Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy'])->name('admin.tags.destroy');

    // 4. CSVエクスポート（応用機能）
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('admin.contacts.export');

});
*/
