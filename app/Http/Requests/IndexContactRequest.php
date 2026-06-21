<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    /**
     * 実際のアクセス制限はルーティングの auth ミドルウェア側で制御
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 検索条件のバリデーションルール
     */
    public function rules(): array
    {
        return [
            'keyword'     => ['nullable', 'string', 'max:255'],
            'gender'      => ['nullable', 'integer', 'in:0,1,2,3'], // 0:全て（リセット時などの考慮）, 1:男性, 2:女性, 3:その他
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date'        => ['nullable', 'date'],
        ];
    }
}