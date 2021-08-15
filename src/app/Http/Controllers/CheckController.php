<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CheckController extends Controller
{

    public function index(Request $request)
    {
        Storage::cloud()->url($this->attributes['path']);
        $updatePhotos[] = Storage::cloud()->putFileAs('photos', $photoFile, $filename, 'public');
        Storage::cloud()->delete($updatePhoto);

        asset('storage/file.txt')

    }
}
