<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class CheckController extends Controller
{
    public function index(Request $request)
    {
        dump(Post::all()->toArray());
    }

    public function show(Request $request, Post $post)
    {
        dump($post->toArray());
    }

    public function store(Request $request, Post $post)
    {
        dump($post->toArray());
    }

    public function destroy(Request $request, Post $post)
    {
        dump($post->toArray());
    }
}
