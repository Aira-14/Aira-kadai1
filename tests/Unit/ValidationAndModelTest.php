<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\IndexContactRequest as ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreContactRequest as ApiStoreRequest;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationAndModelTest extends TestCase
{
    /**
     * CSVエクスポート・一覧検索のバリデーションテスト
     */
    public function test_admin_search_validation()
    {
        $request = new IndexContactRequest;
        $rules = $request->rules();

        // 正常な値
        $v1 = Validator::make(['gender' => 1, 'keyword' => 'テスト'], $rules);
        $this->assertTrue($v1->passes());

        // 不正な性別
        $v2 = Validator::make(['gender' => 4], $rules);
        $this->assertTrue($v2->fails());
    }

    /**
     * 問い合わせ保存のバリデーションテスト（不正な電話番号形式は拒否）
     */
    public function test_contact_store_validation()
    {
        $request = new StoreContactRequest;
        $rules = $request->rules();

        // 不正な電話番号（ハイフンあり、または桁数不足）
        $v = Validator::make(['tel' => '090-1234-5678'], $rules);
        $this->assertTrue($v->fails());
    }

    /**
     * タグ新規登録・更新のバリデーションルール存在確認
     */
    public function test_tag_validation_rules()
    {
        $storeRequest = new StoreTagRequest;
        $this->assertArrayHasKey('name', $storeRequest->rules());

        $updateRequest = new UpdateTagRequest;
        $this->assertArrayHasKey('name', $updateRequest->rules());
    }

    /**
     * API検索・作成バリデーションテスト
     */
    public function test_api_validation_rules()
    {
        $indexApi = new ApiIndexRequest;
        $this->assertContains('in:1,2,3', $indexApi->rules()['gender'] ?? []);

        $storeApi = new ApiStoreRequest;
        $this->assertArrayHasKey('first_name', $storeApi->rules());
    }
}
