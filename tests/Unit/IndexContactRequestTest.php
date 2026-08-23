<?php

namespace Tests\Unit;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function validate(array $data)
    {
        $request = new IndexContactRequest();

        return Validator::make($data, $request->rules());
    }

    /**
     * 【検索】入力が空でも通過すること（全項目 nullable）
     */
    public function test_validation_passes_with_empty_input(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    /**
     * 【検索】全項目を指定した正常値で通過すること
     */
    public function test_validation_passes_with_valid_input(): void
    {
        $category = Category::first();

        $validator = $this->validate([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-08-23',
        ]);

        $this->assertTrue($validator->passes());
    }

    /**
     * 【keyword】255文字以内であること（256文字でエラー）
     */
    public function test_keyword_max_length(): void
    {
        $validator = $this->validate(['keyword' => str_repeat('あ', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keyword', $validator->errors()->toArray());
    }

    /**
     * 【gender】0〜3の整数のみ許可されること
     */
    public function test_gender_must_be_within_allowed_values(): void
    {
        $validator = $this->validate(['gender' => 4]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 【gender】許可された値（0〜3）であれば通過すること
     */
    public function test_gender_accepts_allowed_values(): void
    {
        foreach ([0, 1, 2, 3] as $gender) {
            $validator = $this->validate(['gender' => $gender]);

            $this->assertTrue($validator->passes(), "gender={$gender} は通過するはずです");
        }
    }

    /**
     * 【category_id】存在しないカテゴリIDはエラーになること
     */
    public function test_category_id_must_exist(): void
    {
        $validator = $this->validate(['category_id' => 9999]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    /**
     * 【date】日付形式でない値はエラーになること
     */
    public function test_date_must_be_valid_date_format(): void
    {
        $validator = $this->validate(['date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }
}
