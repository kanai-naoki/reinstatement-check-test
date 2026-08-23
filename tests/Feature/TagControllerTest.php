<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 【認可】未認証ユーザーが編集画面にアクセスすると /login へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_on_edit(): void
    {
        $tag = Tag::first();

        $response = $this->get("/admin/tags/{$tag->id}/edit");

        $response->assertRedirect('/login');
    }

    /**
     * 【認可】未認証ユーザーがタグ作成を行おうとすると /login へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_on_store(): void
    {
        $response = $this->post('/admin/tags', ['name' => '新規タグ']);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('tags', ['name' => '新規タグ']);
    }

    /**
     * 【認可】未認証ユーザーがタグ更新を行おうとすると /login へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_on_update(): void
    {
        $tag = Tag::first();

        $response = $this->put("/admin/tags/{$tag->id}", ['name' => '更新後タグ']);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('tags', ['name' => '更新後タグ']);
    }

    /**
     * 【認可】未認証ユーザーがタグ削除を行おうとすると /login へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_on_destroy(): void
    {
        $tag = Tag::first();

        $response = $this->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    /**
     * 【編集画面】認証済みユーザーが編集画面を表示できること
     */
    public function test_authenticated_user_can_view_edit_page(): void
    {
        $user = User::factory()->create();
        $tag = Tag::first();

        $response = $this->actingAs($user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
        $response->assertViewHas('tag', function ($viewTag) use ($tag) {
            return $viewTag->id === $tag->id;
        });
        $response->assertSee($tag->name);
    }

    /**
     * 【作成】認証済みユーザーが新規タグを作成でき、/admin へリダイレクトされること
     */
    public function test_authenticated_user_can_store_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => '新規タグ']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新規タグ']);
    }

    /**
     * 【更新】認証済みユーザーがタグを更新でき、/admin へリダイレクトされること
     */
    public function test_authenticated_user_can_update_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::first();

        $response = $this->actingAs($user)->put("/admin/tags/{$tag->id}", ['name' => '更新後タグ']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '更新後タグ']);
    }

    /**
     * 【削除】認証済みユーザーがタグを削除でき、/admin へリダイレクトされること
     */
    public function test_authenticated_user_can_destroy_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::first();

        $response = $this->actingAs($user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
