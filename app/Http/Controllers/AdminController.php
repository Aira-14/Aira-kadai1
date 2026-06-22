<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * 管理画面（一覧・検索）
     */
    public function index(IndexContactRequest $request): View
    {
        // 1. 検索用のクエリビルダを初期化（リレーション先も一括取得してN+1問題を回避）
        $query = Contact::with(['category', 'tags']);

        // 2. 検索条件：名前（部分一致：姓・名どちらでも）
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%'); // メールも検索対象に含める場合
            });
        }

        // 3. 検索条件：性別（0:全て 以外の場合に絞り込み）
        if ($request->filled('gender') && $request->input('gender') != 0) {
            $query->where('gender', $request->input('gender'));
        }

        // 4. 検索条件：カテゴリ
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 5. 検索条件：日付（作成日時の一致）
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // 6. 最新順に並び替えて7件ごとにページネーション取得
        $contacts = $query->latest()->paginate(7);

        // 検索窓のドロップダウンで使うカテゴリと、タグ一覧表示用のマスタデータを取得
        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    /**
     * お問い合わせ詳細ページ
     */
    public function show(Contact $contact): View
    {
        return view('admin.show', compact('contact'));
    }

    /**
     * お問い合わせ削除処理
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('admin.index');
    }
}
