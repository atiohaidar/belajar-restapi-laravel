<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Gate;
use Illuminate\Http\Request;

class PostController extends Controller
{
 
    public function update(Request $request, Post $post)
{
    // ini kallo make gate
    if (!Gate::allows('edit-post', $post)) {
        abort(403, 'You do not have permission to edit this post.');
    }

    $post->update($request->only(['title', 'content']));
    return response()->json($post);
}
public function destroy(Post $post)
{
    // ini kalo make gate
    if (!Gate::allows('delete-post', $post)) {
        abort(403, 'You do not have permission to edit this post.');
    }    $post->delete();
    return response()->json(['message' => 'Post deleted']);
}
}
