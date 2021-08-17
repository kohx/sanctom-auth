<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\File;
use App\Models\Post;

class CheckController extends Controller
{
    public function index(Request $request)
    {
        $public = Storage::disk('public');

        $url = $public->url('asdf.txt');
        dump($url); // http://localhost:3000/storage/asdf.txt
    }
}
