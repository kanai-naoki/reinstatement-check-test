<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【Category hasMany Contact】リレーションの型がHasManyであること
     */
    public function test_contacts_relation_is_has_many(): void
    {
        $category = Category::create(['content' => '商品トラブル']);

        $this->assertInstanceOf(HasMany::class, $category->contacts());
    }

    /**
     * 【Category hasMany Contact】1つのカテゴリに紐づく複数のお問い合わせが取得できること
     */
    public function test_category_can_retrieve_related_contacts(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $otherCategory = Category::create(['content' => 'その他']);

        $contacts = Contact::factory()->count(3)->create(['category_id' => $category->id]);
        Contact::factory()->count(2)->create(['category_id' => $otherCategory->id]);

        $this->assertCount(3, $category->contacts);
        $this->assertEqualsCanonicalizing(
            $contacts->pluck('id')->toArray(),
            $category->contacts->pluck('id')->toArray()
        );
    }

    /**
     * 【Category hasMany Contact】お問い合わせが紐づかないカテゴリは空になること
     */
    public function test_category_without_contacts_returns_empty(): void
    {
        $category = Category::create(['content' => '商品トラブル']);

        $this->assertCount(0, $category->contacts);
    }
}
