<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    // フォームから保存されるカラムを網羅して登録
    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    /**
     * categories テーブルとの「多対1」のリレーション
     * Contact belongsTo Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * tags テーブルとの「多対多」のリレーション
     * Contact belongsToMany Tag
     */
    public function tags(): BelongsToMany
    {
        // timestamps() をつけることで中間テーブルのcreated_at/updated_atも自動更新されます
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }
}
