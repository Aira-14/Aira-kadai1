<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * タグの登録・更新・削除ルートテスト
     */
    public function test_admin_can_manage_tags()
    {
        // 1. 認証を通過させるための管理者ユーザー作成
        $user = User::create([
            'name' => 'Admin Tag',
            'email' => 'admin_tag@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. 【Store】タグの登録テスト (引数バリデーションも同時に通過)
        $storeData = ['name' => '新規テストタグ'];
        $response = $this->actingAs($user, 'web')->post(route('tags.store'), $storeData);

        // 登録後のリダイレクト先（一覧画面など）に合わせて検証
        $response->assertStatus(302);
        $this->assertDatabaseHas('tags', ['name' => '新規テストタグ']);

        $tag = Tag::where('name', '新規テストタグ')->first();

        // 3. 【Edit】タグ編集画面の表示テスト
        $response = $this->actingAs($user, 'web')->get(route('tags.edit', $tag->id));
        $response->assertStatus(200);

        // 4. 【Update】タグの更新テスト
        $updateData = ['name' => '更新されたタグ'];
        $response = $this->actingAs($user, 'web')->put(route('tags.update', $tag->id), $updateData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('tags', ['name' => '更新されたタグ']);

        // 5. 【Destroy】タグの削除テスト
        $response = $this->actingAs($user, 'web')->delete(route('tags.destroy', $tag->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
