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
    // if (!Gate::allows('edit-post', $post)) {
    //     abort(403, 'You do not have permission to edit this post.');
    // }
    //  ini make policy (walaupun bisa make untuk gate)
    // Gate::authorize('edit-post', $post); // make gate
    
    Gate::authorize('update', $post); // make policy 

    $post->update($request->only(['title', 'content']));
    return response()->json($post);
}
public function destroy(Post $post)
{
    // https://www.youtube.com/watch?v=q8qKg-LQNxM&list=PLnrs9DcLyeJR6m-IBRINJ0cA69zZHn-uA
    // ini kalo make gate
    //  pro tips, kalau misalnya kita engga bikin instance dari class post, kita bisa make class nya langsung
    // contoh nya jadi Gate::authorize('delete-post', Post::class);
    if (!Gate::allows('delete-post', $post)) {
        abort(403, 'You do not have permission to delete this post.');
    }    

    $post->delete();
    return response()->json(['message' => 'Post deleted']);
}
}
