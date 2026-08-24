<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * ContactSeeder が作成するランダムなお問い合わせを取り除き、検証を決定的にする
     */
    private function clearContacts(): void
    {
        DB::table('contact_tag')->delete();
        Contact::query()->delete();
    }

    /**
     * 【認可】未認証ユーザーは /login へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/contacts/export');

        $response->assertRedirect('/login');
    }

    /**
     * 【認可】認証済み管理者は Status 200 で CSV レスポンスが返ること
     */
    public function test_authenticated_user_can_export_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/contacts/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * 【出力内容】フィルタ条件なしの場合、作成日時の新着順で全件が含まれること
     */
    public function test_export_includes_all_contacts_ordered_by_latest_when_no_filter(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        $older = Contact::factory()->create(['category_id' => $category->id, 'last_name' => '鈴木', 'first_name' => '花子']);
        $older->forceFill(['created_at' => now()->subDays(3)])->save();
        $newer = Contact::factory()->create(['category_id' => $category->id, 'last_name' => '山田', 'first_name' => '太郎']);

        $response = $this->actingAs($user)->get('/contacts/export');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $olderPos = strpos($content, '鈴木');
        $newerPos = strpos($content, '山田');

        $this->assertNotFalse($olderPos);
        $this->assertNotFalse($newerPos);
        $this->assertLessThan($olderPos, $newerPos);
    }

    /**
     * 【出力内容】検索条件（keyword, gender等）を指定した場合、該当するデータのみが含まれること
     */
    public function test_export_includes_only_matching_contacts_when_filtered(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        $matched = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro-yamada@example.com',
            'gender' => 1,
        ]);
        $unmatched = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '鈴木',
            'email' => 'hanako-suzuki@example.com',
            'gender' => 2,
        ]);

        $response = $this->actingAs($user)->get('/contacts/export?keyword=山田&gender=1');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('山田', $content);
        $this->assertStringContainsString($matched->email, $content);
        $this->assertStringNotContainsString($unmatched->email, $content);
    }

    /**
     * 【出力内容】category_id を指定した場合、該当するカテゴリのお問い合わせのみが含まれること
     */
    public function test_export_includes_only_contacts_matching_category_id(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $categories = Category::take(2)->get();

        $matched = Contact::factory()->create([
            'category_id' => $categories[0]->id,
            'email' => 'matched-category@example.com',
        ]);
        $unmatched = Contact::factory()->create([
            'category_id' => $categories[1]->id,
            'email' => 'unmatched-category@example.com',
        ]);

        $response = $this->actingAs($user)->get("/contacts/export?category_id={$categories[0]->id}");

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString($matched->email, $content);
        $this->assertStringNotContainsString($unmatched->email, $content);
    }

    /**
     * 【出力内容】date を指定した場合、該当する作成日時のお問い合わせのみが含まれること
     */
    public function test_export_includes_only_contacts_matching_date(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        $today = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'today@example.com',
        ]);
        $other = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'other-day@example.com',
        ]);
        $other->forceFill(['created_at' => now()->subDays(3)])->save();

        $response = $this->actingAs($user)->get('/contacts/export?date='.now()->toDateString());

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString($today->email, $content);
        $this->assertStringNotContainsString($other->email, $content);
    }

    /**
     * 【出力形式】レスポンスの先頭に BOM (\xEF\xBB\xBF) が含まれていること
     */
    public function test_export_response_starts_with_bom(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/contacts/export');

        $content = $response->streamedContent();

        $this->assertSame("\xEF\xBB\xBF", substr($content, 0, 3));
    }
}
