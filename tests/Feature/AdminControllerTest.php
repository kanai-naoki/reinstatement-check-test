<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * ContactSeeder が作成するランダムなお問い合わせを取り除き、検索・ページネーションの検証を決定的にする
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
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /**
     * 【認可】認証済みユーザーは Status 200 で一覧が表示されること
     */
    public function test_authenticated_user_can_view_admin_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    /**
     * 【検索】keyword で氏名・メールを部分一致検索できること
     */
    public function test_index_filters_by_keyword(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        $matched = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro-yamada@example.com',
        ]);
        $unmatched = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '鈴木',
            'email' => 'hanako-suzuki@example.com',
        ]);

        $response = $this->actingAs($user)->get('/admin?keyword=山田');

        $response->assertStatus(200);
        $contacts = $response->viewData('contacts');

        $this->assertTrue($contacts->contains('id', $matched->id));
        $this->assertFalse($contacts->contains('id', $unmatched->id));
    }

    /**
     * 【検索】gender で性別を絞り込みできること
     */
    public function test_index_filters_by_gender(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        $male = Contact::factory()->create(['category_id' => $category->id, 'gender' => 1]);
        $female = Contact::factory()->create(['category_id' => $category->id, 'gender' => 2]);

        $response = $this->actingAs($user)->get('/admin?gender=1');

        $contacts = $response->viewData('contacts');

        $this->assertTrue($contacts->contains('id', $male->id));
        $this->assertFalse($contacts->contains('id', $female->id));
    }

    /**
     * 【検索】category_id でカテゴリを絞り込みできること
     */
    public function test_index_filters_by_category(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $categories = Category::take(2)->get();

        $matched = Contact::factory()->create(['category_id' => $categories[0]->id]);
        $unmatched = Contact::factory()->create(['category_id' => $categories[1]->id]);

        $response = $this->actingAs($user)->get("/admin?category_id={$categories[0]->id}");

        $contacts = $response->viewData('contacts');

        $this->assertTrue($contacts->contains('id', $matched->id));
        $this->assertFalse($contacts->contains('id', $unmatched->id));
    }

    /**
     * 【検索】date で作成日を絞り込みできること
     */
    public function test_index_filters_by_date(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        $today = Contact::factory()->create(['category_id' => $category->id]);
        $other = Contact::factory()->create(['category_id' => $category->id]);
        $other->forceFill(['created_at' => now()->subDays(3)])->save();

        $response = $this->actingAs($user)->get('/admin?date='.now()->toDateString());

        $contacts = $response->viewData('contacts');

        $this->assertTrue($contacts->contains('id', $today->id));
        $this->assertFalse($contacts->contains('id', $other->id));
    }

    /**
     * 【ページネーション】7件ごとにページネーションされること
     */
    public function test_index_paginates_results_by_seven(): void
    {
        $this->clearContacts();
        $user = User::factory()->create();
        $category = Category::first();

        Contact::factory()->count(10)->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get('/admin');

        $contacts = $response->viewData('contacts');

        $this->assertSame(7, $contacts->perPage());
        $this->assertCount(7, $contacts->items());
        $this->assertSame(10, $contacts->total());

        $secondPageResponse = $this->actingAs($user)->get('/admin?page=2');
        $secondPageContacts = $secondPageResponse->viewData('contacts');

        $this->assertCount(3, $secondPageContacts->items());
    }

    /**
     * 【詳細】指定したお問い合わせがカテゴリ情報付きで表示されること
     */
    public function test_show_displays_contact_with_category(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
        ]);

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.show');
        $response->assertViewHas('contact', function ($viewContact) use ($contact, $category) {
            return $viewContact->id === $contact->id
                && $viewContact->relationLoaded('category')
                && $viewContact->category->id === $category->id;
        });
        $response->assertSee('太郎');
        $response->assertSee('山田');
        $response->assertSee($category->content);
    }

    /**
     * 【削除】お問い合わせが削除され、/admin へリダイレクトされること
     */
    public function test_destroy_deletes_contact_and_redirects_to_index(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
