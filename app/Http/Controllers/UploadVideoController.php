<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadVideoController extends Controller
{
    public function showForm()
    {
        return view('upload-video'); // Blade موجود في resources/views/upload-video.blade.php
    }

    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|mimes:mp4,mov,avi,wmv|max:102400', // 100MB max
        ]);

        // نخزن الفيديو في مجلد pending-videos داخل storage/app
        $path = $request->file('video')->store('pending-videos');

        return back()->with('success', 'تم رفع الفيديو بنجاح في: ' . $path);
    }
}