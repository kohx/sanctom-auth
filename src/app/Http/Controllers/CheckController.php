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
        ## ファイルのURL
        $disk = Storage::disk('public');
        $url = $disk->url('test.txt');
        dump($url);
    }
}
