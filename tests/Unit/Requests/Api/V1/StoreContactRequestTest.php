<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function validate(array $data)
    {
        $request = new StoreContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
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
     * 【保存】全必須項目および tag_ids が正しい場合に通過すること
     */
    public function test_validation_passes_with_valid_data(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    /**
     * 【保存】building は未入力でも通過すること
     */
    public function test_building_is_optional(): void
    {
        $data = $this->validData(['building' => null]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    /**
     * 【保存】tag_ids は未指定でも通過すること
     */
    public function test_tag_ids_is_optional(): void
    {
        $data = $this->validData();
        unset($data['tag_ids']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    /**
     * 【保存】必須項目が未入力の場合にエラーとなること
     */
    public function test_required_fields_are_required(): void
    {
        $requiredFields = [
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ];

        foreach ($requiredFields as $field) {
            $data = $this->validData();
            unset($data[$field]);

            $validator = $this->validate($data);

            $this->assertTrue($validator->fails(), "{$field} が未入力の場合はエラーになるはずです");
            $this->assertArrayHasKey($field, $validator->errors()->toArray());
        }
    }

    /**
     * 【gender】1〜3の整数のみ許可されること
     */
    public function test_gender_must_be_within_allowed_values(): void
    {
        $validator = $this->validate($this->validData(['gender' => 4]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 【email】メール形式でない値はエラーになること
     */
    public function test_email_must_be_valid_format(): void
    {
        $validator = $this->validate($this->validData(['email' => 'not-an-email']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * 【tel】10桁または11桁の数字のみ許可されること
     */
    public function test_tel_accepts_10_or_11_digits(): void
    {
        foreach (['0912345678', '09012345678'] as $tel) {
            $validator = $this->validate($this->validData(['tel' => $tel]));

            $this->assertTrue($validator->passes(), "tel={$tel} は通過するはずです");
        }
    }

    /**
     * 【tel】不正な電話番号形式はエラーになること（桁数不足・超過・数字以外）
     */
    public function test_tel_format_validation(): void
    {
        foreach (['090123456', '090123456789', '090-1234-5678', 'abcdefghijk'] as $tel) {
            $validator = $this->validate($this->validData(['tel' => $tel]));

            $this->assertTrue($validator->fails(), "tel={$tel} はエラーになるはずです");
            $this->assertArrayHasKey('tel', $validator->errors()->toArray());
        }
    }

    /**
     * 【category_id】存在しないカテゴリIDはエラーになること
     */
    public function test_category_id_must_exist(): void
    {
        $validator = $this->validate($this->validData(['category_id' => 9999]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    /**
     * 【tag_ids】存在しないタグIDが含まれる場合はエラーになること
     */
    public function test_tag_ids_must_exist(): void
    {
        $validator = $this->validate($this->validData(['tag_ids' => [9999]]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tag_ids.0', $validator->errors()->toArray());
    }

    /**
     * 【detail】120文字以内であること（121文字でエラー）
     */
    public function test_detail_max_length(): void
    {
        $validator = $this->validate($this->validData(['detail' => str_repeat('あ', 121)]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('detail', $validator->errors()->toArray());
    }
}
