<?php

namespace App\Http\Controllers;

use App\Events\CreateNewPost;
use App\Models\Post;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PostController extends Controller
{
    

    use AuthorizesRequests;
    public function index()
    {
        return view('posts.index', ['posts' => Post::all()->sortByDesc('created_at'), 'userNoPosts' => Auth::user()->posts_count]);
    }

    public function create()
    {
        return view('posts.create', ['users' => [Auth::user()]]);
    }

    public function show(int $id)
    {
        try {
            $post = Post::findOrFail($id);
            return view('posts.show', ['post' => $post]);
        } catch (ModelNotFoundException $err) {
            return response()->view('404', [], 404);
        }
    }

    public function edit(int $id)
    {
        try {
            $post = Post::findOrFail($id);
            $this->authorize('update', $post);
            return view('posts.edit', ['post' => $post, 'users' => [Auth::user()]]);

        } catch (ModelNotFoundException $err) {
            return abort(404, 'Post not found');
        }
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|max:30',
            'body' => 'required|max:255',
        ]);
        $validatedData['user_id'] = Auth::id();

        $post = Post::create($validatedData);
        CreateNewPost::dispatch($post);

        return response()->redirectTo(route('posts.index'))->with('success', 'Post created successfully.');

    }
    public function update(Request $request, $id)
    {

        $post = Post::findOrFail($id);
        $validatedData = $request->validate([
            'title' => 'sometimes|max:30',
            'body' => 'sometimes|max:255',
        ]);
        $validatedData['user_id'] = Auth::id();

        $post->update($validatedData);

        return response()->redirectTo(route('posts.edit', $id))->with('success', 'Post updated successfully.');

    }

    public function destroy($id)
    {
        try {
            $post = Post::findOrFail($id);
            $this->authorize('delete', $post);
            $post->user()->decrement('posts_count');
            $post->delete();

        } catch (ModelNotFoundException $err) {
            // $res = $res->json(['message' => 'Post not found.'], 404);
        }
        return response()->redirectTo(route('posts.index'));

    }

    public function getAllPosts()
    {
        return response()->json(['message' => 'Success', 'data' => Post::all()]);
    }

    public function getPostById($id)
    {
        $res = response();
        try {
            $post = Post::findOrFail($id);
            $res = $res->json(['message' => 'Success.', 'data' => $post]);
        } catch (ModelNotFoundException $err) {
            $res = $res->json(['message' => 'Post not found.'], 404);
        }
        return $res;
    }


}
