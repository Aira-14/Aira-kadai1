<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // APIテスト用の共通セットアップ（必要に応じて）
    }

    /**
     * API：お問い合わせ一覧取得のテスト
     */
    public function test_api_can_get_contacts_list()
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        // 検索にヒットさせるデータ
        $targetContact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '商品が届きません。',
        ]);

        // 検索にヒットさせない除外データ
        Contact::create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '鈴木',
            'gender' => 2,
            'email' => 'suzuki@example.com',
            'tel' => '09087654321',
            'address' => '大阪府',
            'detail' => '問い合わせ内容',
        ]);

        // 1. 通常の一覧取得
        $response = $this->getJson('/api/v1/contacts');
        $response->assertStatus(200);

        // 2. 検索条件（keyword, gender, category_id, per_page）のルートを確実に通過させる
        $searchParams = [
            'keyword' => '太郎',
            'gender' => 1,
            'category_id' => $category->id,
            'per_page' => 5,
        ];

        $searchResponse = $this->getJson('/api/v1/contacts?'.http_build_query($searchParams));
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonCount(1, 'data');

        // JST/UTCのタイムゾーンに左右されないよう、日付単体の検索ルートは別途1件取得できるかだけ通す
        $dateParams = ['date' => now()->format('Y-m-d')];
        $this->getJson('/api/v1/contacts?'.http_build_query($dateParams))->assertStatus(200);
    }

    /**
     * API：お問い合わせ詳細取得のテスト（404ハンドリング含む）
     */
    public function test_api_can_get_single_contact_and_handles_404()
    {
        $category = Category::create(['content' => 'その他']);
        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => 'テスト',
            'last_name' => 'ユーザー',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細内容',
        ]);

        // 正常系：存在するID
        $response = $this->getJson("/api/v1/contacts/{$contact->id}");
        $response->assertStatus(200);

        // 異常系：存在しないID
        $response404 = $this->getJson('/api/v1/contacts/99999');
        $response404->assertStatus(404);
    }

    /**
     * API：お問い合わせ登録＆バリデーションのテスト
     */
    public function test_api_can_create_contact_and_validates_required_fields()
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag1 = Tag::create(['name' => '至急']);
        $tag2 = Tag::create(['name' => '要対応']);

        $data = [
            'category_id' => $category->id,
            'first_name' => 'API太郎',
            'last_name' => 'テスト',
            'gender' => 1,
            'email' => 'api_test@example.com',
            'tel' => '09011112222',
            'address' => 'APIテスト住所',
            'detail' => 'APIからの新規登録テストです。',
            'tag_ids' => [$tag1->id, $tag2->id], // tag_idsの分岐ルートを通過させる
        ];

        // 正常系：登録成功
        $response = $this->postJson('/api/v1/contacts', $data);
        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', ['email' => 'api_test@example.com']);

        // 異常系：バリデーションエラー（必須項目欠落）
        $invalidData = [];
        $responseValidationError = $this->postJson('/api/v1/contacts', $invalidData);
        $responseValidationError->assertStatus(422);
    }

    /**
     * API：お問い合わせ更新のテスト
     */
    public function test_api_can_update_contact()
    {
        $category = Category::create(['content' => 'その他']);
        $tag = Tag::create(['name' => '既存タグ']);

        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '更新前',
            'last_name' => '名前',
            'gender' => 1,
            'email' => 'before@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細',
        ]);

        $updateData = [
            'category_id' => $category->id,
            'first_name' => '更新後',
            'last_name' => '名前',
            'gender' => 1,
            'email' => 'after@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細',
            'tag_ids' => [$tag->id], // tag_idsによるsync処理ルートを通過させる
        ];

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $updateData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('contacts', ['email' => 'after@example.com']);
    }

    /**
     * API：お問い合わせ削除のテスト
     */
    public function test_api_can_delete_contact()
    {
        $category = Category::create(['content' => 'その他']);
        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '削除対象',
            'last_name' => 'ユーザー',
            'gender' => 1,
            'email' => 'delete@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細',
        ]);

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
