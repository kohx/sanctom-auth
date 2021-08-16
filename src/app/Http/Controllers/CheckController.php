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

        /**
         * diskインスタンス取得
         */
        $disk = Storage::disk('public');
        dump($disk);

        /**
         * ファイルのコンテンツを取得
         */
        $file = $disk->get('test.txt');
        dump($file);

        // /**
        //  * ファイルを作成して「'saved!'」内容で保存
        //  */
        // $disk->put('storage/saved.txt', 'saved!');

        // /**
        //  * 指定したPATHにファイルを保存
        //  */

        //  // ストレージに置いたsample/sample.txtをファイルオブジェクトとして取得
        // $file_path = storage_path('sample/sample.txt');
        // $tmpFile = new File($file_path);

        // $file = $disk->putFile(`storage`, $tmpFile);
    }
}
