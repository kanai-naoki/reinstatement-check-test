<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class AdminController extends Controller
{
    // お問い合わせ一覧表示・検索機能
    public function index(IndexContactRequest $request)
    {
        $query = Contact::query()->with(['category', 'tags']);

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $keyword = $request->input('keyword');
            $q->where(function ($sub) use ($keyword) {
                $sub->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        });

        $query->when($request->filled('gender') && $request->input('gender') != 0, function ($q) use ($request) {
            $q->where('gender', $request->input('gender'));
        });

        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('category_id', $request->input('category_id'));
        });

        $query->when($request->filled('date'), function ($q) use ($request) {
            $q->whereDate('created_at', $request->input('date'));
        });

        $contacts = $query->paginate(7)->appends($request->query());

        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    // お問い合わせ詳細画面
    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    // お問い合わせ削除
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.index');
    }
}
