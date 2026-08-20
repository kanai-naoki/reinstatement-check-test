<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;

class TagController extends Controller
{
    // タグ編集画面の表示
    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    // タグの新規追加
    public function store(TagRequest $request)
    {

        Tag::create($request->validated());

        return redirect('/admin')->with('message', 'タグを追加しました');
    }

    // タグ更新機能
    public function update(TagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return redirect('/admin');
    }

    // タグの削除
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect('/admin')->with('message', 'タグを削除しました');
    }
}
