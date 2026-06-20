<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    // 安全に保存（一括入力を許可）するカラムを指定
    protected $fillable = ['content'];

    /**
     * contacts テーブルとの「1対多」のリレーション
     * Category hasMany Contact
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
