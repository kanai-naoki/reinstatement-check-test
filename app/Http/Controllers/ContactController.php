<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    // お問い合わせフォーム入力画面
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact/index', compact('categories', 'tags'));
    }

    // お問い合わせ確認画面
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

    // お問い合わせ保存
    public function store(StoreContactRequest $request)
    {
        $inputs = $request->validated();

        $contact = Contact::create($inputs);

        if ($request->filled('tag_ids')) {
            $contact->tags()->sync($request->input('tag_ids'));
        }

        return redirect()->route('contact.thanks');
    }

    // お問い合わせサンクス画面を表示
    public function thanks()
    {
        return view('contact.thanks');
    }

    // CSVエクスポート機能
    public function export(ExportContactRequest $request): StreamedResponse
    {
        $query = Contact::with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('gender') && $request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->latest()->get();

        $response = new StreamedResponse(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時'
            ]);

            $genderMap = [1 => '男性', 2 => '女性', 3 => 'その他'];

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $contact->first_name . ' ' . $contact->last_name,
                    $genderMap[$contact->gender] ?? '不明',
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category?->content ?? '',
                    $contact->detail,
                    $contact->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="contacts_' . date('Ymd_His') . '.csv"');

        return $response;
    }
}
