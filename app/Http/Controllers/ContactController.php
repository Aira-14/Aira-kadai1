<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
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
}
