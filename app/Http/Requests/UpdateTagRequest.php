<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ルートパラメータから、現在編集しようとしているtagのIDを取得
        $tagId = $this->route('tag')?->id ?? $this->route('tag');

        return [
            // 自身のIDを除外してユニークチェックを行う（自身の現在名は重複エラーにしない）
            'name' => [
                'required', 
                'string', 
                'max:50', 
                Rule::unique('tags', 'name')->ignore($tagId)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'タグ名を入力してください',
            'name.max'      => 'タグ名は50文字以内で入力してください',
            'name.unique'   => 'そのタグ名は既に使用されています',
        ];
    }
}