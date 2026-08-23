<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【Tag belongsToMany Contact】リレーションの型がBelongsToManyであること
     */
    public function test_contacts_relation_is_belongs_to_many(): void
    {
        $tag = Tag::create(['name' => '質問']);

        $this->assertInstanceOf(BelongsToMany::class, $tag->contacts());
    }

    /**
     * 【Tag belongsToMany Contact】1つのタグが複数のお問い合わせに紐づいていること
     */
    public function test_tag_can_retrieve_related_contacts(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $tag = Tag::create(['name' => '質問']);
        $otherTag = Tag::create(['name' => '要望']);

        $contacts = Contact::factory()->count(3)->create(['category_id' => $category->id]);
        $otherContact = Contact::factory()->create(['category_id' => $category->id]);

        foreach ($contacts as $contact) {
            $contact->tags()->sync([$tag->id]);
        }
        $otherContact->tags()->sync([$otherTag->id]);

        $this->assertCount(3, $tag->contacts);
        $this->assertEqualsCanonicalizing(
            $contacts->pluck('id')->toArray(),
            $tag->contacts->pluck('id')->toArray()
        );
    }

    /**
     * 【Tag belongsToMany Contact】どのお問い合わせにも紐づかないタグは空になること
     */
    public function test_tag_without_contacts_returns_empty(): void
    {
        $tag = Tag::create(['name' => '質問']);

        $this->assertCount(0, $tag->contacts);
    }
}
