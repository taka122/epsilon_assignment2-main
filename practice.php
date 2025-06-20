// routes/web.php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('posts', PostController::class);
});

// app/Http/Controllers/PostController.php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::latest()->paginate(10);
        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        return view('posts.create');
    }

    public function store(StorePostRequest $request)
    {
        $post = new Post($request->validated());
        $post->user_id = auth()->id();
        $post->save();

        return redirect()->route('posts.index');
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }

    public function update(StorePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);
        $post->update($request->validated());

        return redirect()->route('posts.index');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();

        return redirect()->route('posts.index');
    }
}

// app/Http/Requests/StorePostRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'body' => 'required',
        ];
    }
}

// resources/views/posts/index.blade.php

@extends('layouts.app')
@section('content')
<h1>投稿一覧</h1>
<a href="{{ route('posts.create') }}">新規投稿</a>
@foreach($posts as $post)
    <h3>{{ $post->title }}</h3>
    <p>{{ $post->body }}</p>
    <a href="{{ route('posts.edit', $post) }}">編集</a>
    <form action="{{ route('posts.destroy', $post) }}" method="POST">
        @csrf @method('DELETE')
        <button>削除</button>
    </form>
@endforeach
{{ $posts->links() }}
@endsection

// resources/views/posts/create.blade.php

@extends('layouts.app')
@section('content')
<h1>新規投稿</h1>
<form action="{{ route('posts.store') }}" method="POST">
    @csrf
    <label>タイトル</label>
    <input type="text" name="title">
    <label>本文</label>
    <textarea name="body"></textarea>
    <button type="submit">投稿</button>
</form>
@endsection

// resources/views/posts/edit.blade.php

@extends('layouts.app')
@section('content')
<h1>投稿編集</h1>
<form action="{{ route('posts.update', $post) }}" method="POST">
    @csrf @method('PUT')
    <label>タイトル</label>
    <input type="text" name="title" value="{{ $post->title }}">
    <label>本文</label>
    <textarea name="body">{{ $post->body }}</textarea>
    <button type="submit">更新</button>
</form>
@endsection
