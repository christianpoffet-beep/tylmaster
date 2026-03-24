<?php
// Public preview controller for shared content posts
namespace App\Http\Controllers;

use App\Models\ContentPost;

class ContentPreviewController extends Controller
{
    public function show(string $token)
    {
        $post = ContentPost::with('images')->where('share_token', $token)->firstOrFail();

        return view('public.content-preview', compact('post'));
    }
}
