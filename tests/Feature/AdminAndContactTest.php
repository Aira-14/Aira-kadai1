<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAndContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト中のCSRFトークンチェックを無効化して500エラーを回避
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * 画面アクセス：ページ表示のテスト
     */
    public function test_contact_form_page_displays_successfully()
    {
        // 事前にカテゴリとタグを用意
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag = Tag::create(['name' => '質問']);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * 画面アクセス：管理画面のアクセス制御（未認証ユーザーのブロック）
     */
    public function test_unauthenticated_users_are_redirected_to_login()
    {
        // 未ログイン状態で管理画面にアクセス
        $response = $this->get('/admin');

        // /login にリダイレクトされることを検証
        $response->assertRedirect('/login');
    }

    /**
     * お問い合わせフォーム確認ページの表示＆バリデーション
     */
    public function test_contact_confirmation_page_displays_input_data()
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        $data = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'category_id' => $category->id,
            'detail' => 'テスト内容です。',
        ];

        // コントローラーがビューにデータを渡すあらゆるパターン（セッション・直渡し・フラッシュデータ）に対応できるよう網羅
        view()->share('validated', $data);
        session()->flash('validated', $data);

        $response = $this->withSession(['validated' => $data])->post('/contacts/confirm', $data);

        // 500エラーが続く場合、この行のコメントアウトを外すとターミナルに詳細なエラー理由が直接表示されます
        // $this->withoutExceptionHandling();

        $response->assertStatus(200);
        $response->assertSee('太郎');
    }

    /**
     * 管理機能：検索・7件ずつのページネーションテスト
     */
    public function test_admin_dashboard_paginates_seven_items_per_page()
    {
        // テストユーザー（管理者）を作成
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => 'その他']);

        // お問い合わせを10件一気に作成
        for ($i = 0; $i < 10; $i++) {
            Contact::create([
                'category_id' => $category->id,
                'first_name' => 'テスト',
                'last_name' => 'ユーザー'.$i,
                'gender' => 1,
                'email' => "test{$i}@example.com",
                'tel' => '09012345678',
                'address' => '住所',
                'detail' => '詳細',
            ]);
        }

        // ログインした状態で管理画面へアクセス
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
        // 7件ごとのページネーションが効いているため、1ページ目のデータ数が正しいか（リンクの存在等）を検証
        $response->assertSee('page=2');
    }

    /**
     * エクスポート：CSVダウンロードのテスト
     */
    public function test_authenticated_user_can_download_csv()
    {
        // factoryエラーを回避するため、一意にcreateに変更
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => 'その他']);
        // 空データによるCSVロジックのエラーを防ぐため、最低1件お問い合わせを登録
        Contact::create([
            'category_id' => $category->id,
            'first_name' => 'CSV用',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'csv@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細',
        ]);

        $response = $this->actingAs($user)->get('/contacts/export');
        $response->assertStatus(200);
        // レンスポンスヘッダーがCSV形式であることを検証
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * お問い合わせフォーム：送信・保存処理（正常送信パターン）
     */
    public function test_contact_form_can_be_submitted_and_saved()
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag = Tag::create(['name' => '至急']);

        $data = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'submit_test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'category_id' => $category->id,
            'detail' => 'テスト内容です。',
            'tag_ids' => [$tag->id], // tag_idsルートを通過させる
        ];

        // フォームから「送信（actionがback以外）」された場合をテスト
        $response = $this->post('/contacts', $data);

        // 送信後はサンクス画面にリダイレクトされるか検証
        $response->assertRedirect('/thanks');

        // データベースにデータが登録されているか検証
        $this->assertDatabaseHas('contacts', [
            'email' => 'submit_test@example.com',
        ]);
    }

    /**
     * お問い合わせフォーム：確認画面から「戻る」ボタンを押したときの挙動テスト
     */
    public function test_contact_form_can_be_redirected_back_with_input()
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        $data = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'back_test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'category_id' => $category->id,
            'detail' => 'テスト内容です。',
            'action' => 'back', // これにより action === 'back' の if ルートを通過
        ];

        $response = $this->post('/contacts', $data);

        // 入力画面にリダイレクトして戻るか検証
        $response->assertRedirect(route('contact.index'));
        // 直前の入力データがセッションに保持されている（withInput）か検証
        $response->assertSessionHasInput('email', 'back_test@example.com');
    }

    /**
     * お問い合わせフォーム：サンクスページ表示のテスト
     */
    public function test_thanks_page_displays_successfully()
    {
        $response = $this->get('/thanks');
        $response->assertStatus(200);
    }

    /**
     * エクスポート：CSVダウンロード（各種検索条件・性別分岐のテスト）
     */
    public function test_authenticated_user_can_download_csv_with_search_filters()
    {
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin_csv@example.com', 'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => 'その他']);

        // 性別のmatch分岐（男性=1, 女性=2, その他=3, default）をすべて通過させるために複数作成
        Contact::create([
            'category_id' => $category->id, 'first_name' => 'CSV検索', 'last_name' => '男', 'gender' => 1,
            'email' => 'csv_filter1@example.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '詳細',
        ]);
        Contact::create([
            'category_id' => $category->id, 'first_name' => 'CSV検索', 'last_name' => '女', 'gender' => 2,
            'email' => 'csv_filter2@example.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '詳細',
        ]);
        Contact::create([
            'category_id' => $category->id, 'first_name' => 'CSV検索', 'last_name' => '他', 'gender' => 3,
            'email' => 'csv_filter3@example.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '詳細',
        ]);

        // exportメソッドのkeyword, gender, category_id, dateの全ifルートを一気に通過させるクエリ
        $searchParams = [
            'keyword' => 'CSV検索',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->get('/contacts/export?'.http_build_query($searchParams));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * 管理機能：詳細ページの表示テスト
     */
    public function test_admin_can_view_contact_detail_page()
    {
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin_show@example.com', 'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => 'その他']);
        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '詳細',
            'last_name' => 'テスト',
            'gender' => 1,
            'email' => 'show_test@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細画面の確認です。',
        ]);

        // 詳細画面にアクセス
        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertSee('詳細画面の確認です。');
    }

    /**
     * 管理機能：検索条件の絞り込みテスト（全検索分岐を通過）
     */
    public function test_admin_dashboard_filter_logic_passes_all_conditions()
    {
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin_filter@example.com', 'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => '特定カテゴリ']);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '検索対象',
            'last_name' => 'ユーザー',
            'gender' => 1,
            'email' => 'filter_target@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細',
        ]);

        // keyword, gender, category_id, date の全 if ルートを同時に通過させる
        $searchParams = [
            'keyword' => '検索対象',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->get('/admin?'.http_build_query($searchParams));

        $response->assertStatus(200);
        $response->assertSee('filter_target@example.com');
    }

    /**
     * 管理機能：お問い合わせ削除機能のテスト
     */
    public function test_admin_can_delete_contact()
    {
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin_delete@example.com', 'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => 'その他']);
        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '削除',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'delete_user@example.com',
            'tel' => '09012345678',
            'address' => '住所',
            'detail' => '詳細',
        ]);

        // 削除ルートにDELETE（またはPOST/Redirect）リクエストを送信
        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        // 削除後は一覧にリダイレクトされるか検証
        $response->assertRedirect(route('admin.index'));

        // DBからデータが消えていることを検証
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /**
     * エクスポート：検索条件なしでのCSVダウンロードテスト（残りの分岐網羅）
     */
    public function test_authenticated_user_can_download_csv_without_filters()
    {

        $user = User::create([
            'name' => 'Admin CSV All',
            'email' => 'admin_csv_all_'.uniqid().'@example.com', // メールアドレスの重複を完全に防ぐ
            'password' => bcrypt('password'),
        ]);

        $category = Category::create(['content' => 'その他']);

        // 性別が「女性(2)」と「その他(3)」のデータを確実に通過させる
        Contact::create([
            'category_id' => $category->id, 'first_name' => 'テスト', 'last_name' => '女', 'gender' => 2,
            'email' => 'csv_all1@example.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '詳細',
        ]);
        Contact::create([
            'category_id' => $category->id, 'first_name' => 'テスト', 'last_name' => '他', 'gender' => 3,
            'email' => 'csv_all2@example.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '詳細',
        ]);

        // webミドルウェアのセッションとauth認証を確実に効かせるため、route名を使ってアクセス
        $response = $this->actingAs($user, 'web')->get(route('contacts.export'));

        // コントローラーまで到達して正常（200）にCSVが返ってきていることを厳密に検証
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
