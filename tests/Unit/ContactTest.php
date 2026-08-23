<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【Contact belongsTo Category】リレーションの型がBelongsToであること
     */
    public function test_category_relation_is_belongs_to(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(BelongsTo::class, $contact->category());
    }

    /**
     * 【Contact belongsTo Category】1つのお問い合わせが特定のカテゴリに属すること
     */
    public function test_contact_belongs_to_its_category(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertSame($category->id, $contact->category->id);
        $this->assertSame('商品トラブル', $contact->category->content);
    }

    /**
     * 【Contact belongsToMany Tag】リレーションの型がBelongsToManyであること
     */
    public function test_tags_relation_is_belongs_to_many(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(BelongsToMany::class, $contact->tags());
    }

    /**
     * 【Contact belongsToMany Tag】syncで複数のタグと同調できること
     */
    public function test_contact_can_sync_multiple_tags(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $tag1 = Tag::create(['name' => '質問']);
        $tag2 = Tag::create(['name' => '要望']);
        $tag3 = Tag::create(['name' => '不具合報告']);

        $contact->tags()->sync([$tag1->id, $tag2->id]);

        $this->assertCount(2, $contact->tags);
        $this->assertEqualsCanonicalizing(
            [$tag1->id, $tag2->id],
            $contact->tags->pluck('id')->toArray()
        );

        // sync で置き換えると古い紐付けが解除されること
        $contact->tags()->sync([$tag3->id]);
        $contact->refresh();

        $this->assertCount(1, $contact->tags);
        $this->assertSame($tag3->id, $contact->tags->first()->id);
    }
}
