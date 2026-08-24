<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
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
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
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
     * 【検索】キーワード・性別・カテゴリーID・日付・per_page が正しい場合に通過すること
     */
    public function test_validation_passes_with_valid_input(): void
    {
        $category = Category::first();

        $validator = $this->validate([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-08-23',
            'per_page' => 10,
        ]);

        $this->assertTrue($validator->passes());
    }

    /**
     * 【gender】1〜3の整数のみ許可されること
     */
    public function test_gender_accepts_allowed_values(): void
    {
        foreach ([1, 2, 3] as $gender) {
            $validator = $this->validate(['gender' => $gender]);

            $this->assertTrue($validator->passes(), "gender={$gender} は通過するはずです");
        }
    }

    /**
     * 【gender】不正な性別（例: 4）はエラーになること
     */
    public function test_gender_must_be_within_allowed_values(): void
    {
        $validator = $this->validate(['gender' => 4]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 【category_id】categories テーブルに存在するIDであれば通過すること
     */
    public function test_category_id_passes_when_it_exists(): void
    {
        $category = Category::first();

        $validator = $this->validate(['category_id' => $category->id]);

        $this->assertTrue($validator->passes());
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
     * 【date】Y-m-d 形式の値であれば通過すること
     */
    public function test_date_passes_with_valid_format(): void
    {
        $validator = $this->validate(['date' => '2026-08-24']);

        $this->assertTrue($validator->passes());
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

    /**
     * 【per_page】数値であれば通過すること
     */
    public function test_per_page_passes_with_numeric_value(): void
    {
        $validator = $this->validate(['per_page' => 20]);

        $this->assertTrue($validator->passes());
    }

    /**
     * 【per_page】文字列が渡された場合はエラーになること
     */
    public function test_per_page_rejects_non_numeric_string(): void
    {
        $validator = $this->validate(['per_page' => 'abc']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }

    /**
     * 【per_page】負の値が渡された場合はエラーになること
     */
    public function test_per_page_rejects_negative_value(): void
    {
        $validator = $this->validate(['per_page' => -1]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }
}
