<?php

namespace Tests\Unit;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TagRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * ルートとバインドされた TagRequest を安全に生成するヘルパー関数
     */
    private function createTagRequest(array $data, $tagParam = null): TagRequest
    {
        $request = TagRequest::create('/admin/tags', 'POST', $data);

        // ダミーのルートを作成してコンテナにバインド
        $route = RouteFacade::get('/admin/tags/{tag?}', fn () => null);

        $route->bind($request);

        if ($tagParam !== null) {
            $route->setParameter('tag', $tagParam);
        }
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        return $request;
    }

    /**
     * 【新規登録】正常値で通過すること
     */
    public function test_tag_store_validation_passes(): void
    {
        $request = $this->createTagRequest(['name' => '未登録の新規タグ']);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /**
     * 【新規登録】タグ名が必須であること
     */
    public function test_tag_name_is_required(): void
    {
        $request = $this->createTagRequest(['name' => '']);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * 【新規登録】タグ名が50文字以内であること（51文字でエラー）
     */
    public function test_tag_name_max_length(): void
    {
        $request = $this->createTagRequest(['name' => str_repeat('あ', 51)]);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * 【新規登録】重複しているタグ名は拒否すること
     */
    public function test_tag_name_must_be_unique_on_store(): void
    {
        $existingTag = Tag::first();

        $request = $this->createTagRequest(['name' => $existingTag->name]);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * 【更新】自身の名前維持は可能であること
     */
    public function test_tag_name_can_be_maintained_on_update(): void
    {
        $existingTag = Tag::first();

        $request = $this->createTagRequest(['name' => $existingTag->name], $existingTag->id);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    /**
     * 【更新】他で既に使用されているタグ名への変更は拒否すること
     */
    public function test_tag_name_cannot_be_changed_to_existing_tag_name_on_update(): void
    {
        $tags = Tag::take(2)->get();
        $tag1 = $tags[0];
        $tag2 = $tags[1];

        $request = $this->createTagRequest(['name' => $tag2->name], $tag1->id);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}