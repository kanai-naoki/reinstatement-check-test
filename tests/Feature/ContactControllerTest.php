<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
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

    /**
     * 【トップページ】Status 200 で表示され、categories・tags がビューに渡されること
     */
    public function test_index_page_is_displayed_with_categories_and_tags(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');

        foreach (Category::all() as $category) {
            $response->assertSee($category->content);
        }
        foreach (Tag::all() as $tag) {
            $response->assertSee($tag->name);
        }
    }

    /**
     * 【サンクスページ】Status 200 で正常に表示されること
     */
    public function test_thanks_page_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertStatus(200);
        $response->assertViewIs('contact.thanks');
        $response->assertSee('お問い合わせありがとうございました');
    }

    /**
     * 【確認画面】バリデーション通過時に入力内容が表示されること
     */
    public function test_confirm_page_is_displayed_with_input_data_when_validation_passes(): void
    {
        $category = Category::first();
        $data = $this->validData(['category_id' => $category->id]);

        $response = $this->post('/contacts/confirm', $data);

        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');
        $response->assertSee($data['first_name']);
        $response->assertSee($data['last_name']);
        $response->assertSee($data['email']);
        $response->assertSee($category->content);
    }

    /**
     * 【確認画面】バリデーションエラー時はリダイレクトされ、エラーが返ること
     */
    public function test_confirm_redirects_back_with_errors_when_validation_fails(): void
    {
        $data = $this->validData(['first_name' => '']);

        $response = $this->post('/contacts/confirm', $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('first_name');
    }

    /**
     * 【送信】バリデーション通過時に contacts・contact_tag へ保存され、/thanks へリダイレクトされること
     */
    public function test_store_saves_contact_and_tags_then_redirects_to_thanks(): void
    {
        $tagIds = Tag::take(2)->pluck('id')->toArray();
        $data = $this->validData(['tag_ids' => $tagIds]);

        $response = $this->post('/contacts', $data);

        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'category_id' => $data['category_id'],
        ]);

        $contact = Contact::where('email', $data['email'])->firstOrFail();

        $this->assertCount(2, $contact->tags);
        $this->assertEqualsCanonicalizing($tagIds, $contact->tags->pluck('id')->toArray());

        foreach ($tagIds as $tagId) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tagId,
            ]);
        }
    }

    /**
     * 【送信】バリデーションエラー時はリダイレクトされ、エラーが返ること
     */
    public function test_store_redirects_back_with_errors_when_validation_fails(): void
    {
        $data = $this->validData(['email' => 'not-an-email']);

        $response = $this->post('/contacts', $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('contacts', [
            'tel' => $data['tel'],
        ]);
    }
}
