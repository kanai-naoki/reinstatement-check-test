<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class ContactController extends Controller
{
    // お問い合わせフォーム入力ページ
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact/index', compact('categories', 'tags'));
    }

    // 確認画面を表示
    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $category = Category::find($request->category_id);
        $tags = Tag::whereIn('id', $request->input('tag_ids', []))->get();

        return view('contact.confirm', compact(
            'validated',
            'category',
            'tags'
        ));
    }

    // お問い合わせを保存
    public function store(StoreContactRequest $request)
    {
        $inputs = $request->validated();

        Contact::create($inputs);

        return redirect()->route('contact.thanks');
    }

    // サンクス画面を表示
    public function thanks()
    {
        return view('contact.thanks');
    }
}
