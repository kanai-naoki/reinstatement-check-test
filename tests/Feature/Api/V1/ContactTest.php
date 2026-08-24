<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'building' => 'サンプルビル101',
            'category_id' => Category::first()->id,
            'tag_ids' => Tag::take(2)->pluck('id')->toArray(),
            'detail' => 'お問い合わせ内容のサンプルです。',
        ], $overrides);
    }

    // ==================================================
    // GET /api/v1/contacts（一覧）
    // ==================================================

    /**
     * 【一覧】JSON形式で一覧が返ること
     */
    public function test_index_returns_contact_list_as_json(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'gender',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'category',
                    'tags',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    /**
     * 【一覧】keyword・category_id で検索できること
     */
    public function test_index_filters_by_keyword_and_category(): void
    {
        $category = Category::first();
        $other = Category::where('id', '!=', $category->id)->first();

        $target = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => 'ユニーク太郎',
        ]);
        Contact::factory()->create([
            'category_id' => $other->id,
            'first_name' => '別の名前',
        ]);

        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'keyword' => 'ユニーク太郎',
            'category_id' => $category->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $target->id);
    }

    /**
     * 【一覧】gender で検索できること
     */
    public function test_index_filters_by_gender(): void
    {
        $target = Contact::factory()->create(['gender' => 2, 'email' => 'gender-filter-target@example.com']);

        $response = $this->getJson('/api/v1/contacts?gender=2&per_page=100');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
        foreach ($response->json('data') as $item) {
            $this->assertEquals(2, $item['gender']);
        }
    }

    /**
     * 【一覧】date で検索できること
     */
    public function test_index_filters_by_date(): void
    {
        $target = Contact::factory()->create(['created_at' => '2026-08-20 10:00:00']);
        Contact::factory()->create(['created_at' => '2026-08-21 10:00:00']);

        $response = $this->getJson('/api/v1/contacts?date=2026-08-20&per_page=100');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
        foreach ($response->json('data') as $item) {
            $this->assertStringStartsWith('2026-08-20', $item['created_at']);
        }
    }

    /**
     * 【一覧】per_page でページネーションが機能すること
     */
    public function test_index_paginates_with_per_page(): void
    {
        Contact::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=2');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
    }

    /**
     * 【一覧】バリデーションエラー時に422が返ること
     */
    public function test_index_returns_422_when_validation_fails(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=4');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gender');
    }

    // ==================================================
    // GET /api/v1/contacts/{id}（詳細）
    // ==================================================

    /**
     * 【詳細】JSON形式で詳細が返ること
     */
    public function test_show_returns_contact_detail_as_json(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.email', $contact->email);
    }

    /**
     * 【詳細】存在しないIDの場合は404とエラーメッセージが返ること
     */
    public function test_show_returns_404_when_contact_not_found(): void
    {
        $response = $this->getJson('/api/v1/contacts/9999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    // ==================================================
    // POST /api/v1/contacts（作成）
    // ==================================================

    /**
     * 【作成】レコードが作成され201が返ること
     */
    public function test_store_creates_contact_and_returns_201(): void
    {
        $data = $this->validData();

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.email', $data['email']);

        $this->assertDatabaseHas('contacts', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        $contact = Contact::where('email', $data['email'])->firstOrFail();
        $this->assertEqualsCanonicalizing($data['tag_ids'], $contact->tags->pluck('id')->toArray());
    }

    /**
     * 【作成】バリデーションエラー時に422が返ること
     */
    public function test_store_returns_422_when_validation_fails(): void
    {
        $data = $this->validData(['email' => 'not-an-email']);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('contacts', [
            'tel' => $data['tel'],
        ]);
    }

    // ==================================================
    // PUT /api/v1/contacts/{id}（更新）
    // ==================================================

    /**
     * 【更新】レコードが更新され200が返ること
     */
    public function test_update_updates_contact_and_returns_200(): void
    {
        $contact = Contact::factory()->create();
        $data = $this->validData(['first_name' => '更新太郎']);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonPath('data.first_name', '更新太郎');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '更新太郎',
        ]);
    }

    /**
     * 【更新】存在しないIDの場合は404が返ること
     */
    public function test_update_returns_404_when_contact_not_found(): void
    {
        $data = $this->validData();

        $response = $this->putJson('/api/v1/contacts/9999', $data);

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    /**
     * 【更新】バリデーションエラー時に422が返ること
     */
    public function test_update_returns_422_when_validation_fails(): void
    {
        $contact = Contact::factory()->create();
        $data = $this->validData(['email' => 'not-an-email']);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    // ==================================================
    // DELETE /api/v1/contacts/{id}（削除）
    // ==================================================

    /**
     * 【削除】レコードが削除され204が返ること
     */
    public function test_destroy_deletes_contact_and_returns_204(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /**
     * 【削除】存在しないIDの場合は404が返ること
     */
    public function test_destroy_returns_404_when_contact_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/9999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }
}
