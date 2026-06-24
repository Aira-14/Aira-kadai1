<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * お問い合わせフォーム入力ページ
     */
    public function index(): View
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    /**
     * お問い合わせフォーム確認ページ
     */
    public function confirm(StoreContactRequest $request): View
    {
        $inputs = $request->validated();

        $category = Category::find($inputs['category_id']);

        $selectedTags = isset($inputs['tag_ids'])
            ? Tag::whereIn('id', $inputs['tag_ids'])->get()
            : collect();

        return view('contact.confirm', compact('inputs', 'category', 'selectedTags'));
    }

    /**
     * お問い合わせ完了処理
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        if ($request->input('action') === 'back') {
            return redirect()->route('contact.index')->withInput();
        }

        $contact = Contact::create($request->validated());

        if ($request->has('tag_ids')) {
            $contact->tags()->sync($request->input('tag_ids'));
        }

        return redirect()->route('contact.thanks');
    }

    /**
     * サンクスページ表示
     */
    public function thanks(): View
    {
        return view('contact.thanks');
    }

    /**
     * 検索条件に一致するデータをBOM付きCSVとしてエクスポート
     */
    public function export(Request $request)
    {
        // 1. 検索クエリの構築（一覧画面と共通の絞り込みロジック）
        $query = Contact::with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%');
            });
        }

        if ($request->filled('gender') && $request->input('gender') != 0) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // 新着順（最新順）で全件取得
        $contacts = $query->latest()->get();

        // 2. CSV出力の準備（Streamを用いてメモリに優しく処理）
        $callback = function () use ($contacts) {
            $file = fopen('php://output', 'w');

            // 【重要】Excel文字化けを防ぐ「BOM（Byte Order Mark）」をファイルの先頭に出力
            fwrite($file, pack('C*', 0xEF, 0xBB, 0xBF));

            // ヘッダー行を出力
            $header = ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'];
            fputcsv($file, $header);

            foreach ($contacts as $contact) {
                $genderStr = match ($contact->gender) {
                    1 => '男性',
                    2 => '女性',
                    3 => 'その他',
                    default => '不明',
                };

                fputcsv($file, [
                    $contact->id,
                    $contact->first_name.' '.$contact->last_name,
                    $genderStr,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category?->name ?? '未選択',
                    $contact->detail,
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        // 3. レスポンスヘッダーの設定（ファイル名を指定してダウンロードさせる）
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts_'.now()->format('YmdHis').'.csv"',
        ];

        return response()->stream($callback, 200, $headers);
    }
}
