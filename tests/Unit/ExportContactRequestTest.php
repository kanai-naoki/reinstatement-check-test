<?php

namespace Tests\Unit;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function validate(array $data)
    {
        $request = new ExportContactRequest;

        return Validator::make($data, $request->rules());
    }

    /**
     * 【エクスポート】全項目を指定した正常値で通過すること
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
     * 【gender】許可されていない値（例: 99）はエラーになること
     */
    public function test_gender_must_be_within_allowed_values(): void
    {
        $validator = $this->validate(['gender' => 99]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
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
}
