<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    /**
     * AP01: お問い合わせ一覧取得
     */
    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%');
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // デフォルト20件でページネーション
        $perPage = $request->input('per_page', 20);
        $contacts = $query->latest()->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    /**
     * AP02: お問い合わせ詳細取得
     */
    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact->load(['category', 'tags']));
    }

    /**
     * API03: お問い合わせ登録
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::create($request->validated());

        if ($request->has('tag_ids')) {
            $contact->tags()->attach($request->input('tag_ids'));
        }

        return (new ContactResource($contact->load(['category', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * AP04: お問い合わせ更新
     */
    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $contact->update($request->validated());

        if ($request->has('tag_ids')) {
            $contact->tags()->sync($request->input('tag_ids'));
        }

        return (new ContactResource($contact->load(['category', 'tags'])))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * AP05: お問い合わせ削除
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
