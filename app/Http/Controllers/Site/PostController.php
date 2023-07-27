<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\LikeRequest;
use App\Http\Requests\Site\PostRequest;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostPhoto;
use App\Models\SavePost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function store(PostRequest $request)
    {
        $post = Post::query()
            ->create([
                'text' => $request->text,
                'created_by' => $request->created_by,
                'location' => $request->location
            ]);

        foreach ($request->photos ?? [] as $photo) {
            $fileName = time() . rand(1, 1000) . '.' . $photo->extension();
            $fileNameWithUpload = 'storage/uploads/posts/' . $fileName;

            $photo->storeAs('public/uploads/posts/', $fileName);

            PostPhoto::query()
                ->create([
                    'post_id' => $post->id,
                    'photo' => $fileNameWithUpload
                ]);
        }

        return response([
            "message" => "Post created",
            "data" => null
        ], 201);
    }

    public function index()
    {
        $posts = Post::query()
            ->from('posts as p')
            ->select(
                'p.id',
                'p.text',
                'p.created_by',
                'p.created_at',
                'p.location',
            )
            ->leftJoin('users as u', 'u.id', 'p.created_by')
            ->whereNull('deleted_at')
            ->whereNull('is_archived')
            ->with('photos')
            ->orderByDesc('created_at')
            ->get();

        return response([
            "message" => "Posts retrieved successfully",
            "data" => [
                'posts' => $posts
            ]
        ], 200);
    }

    public function show($id, Request $request)
    {
        $post = Post::query()
            ->from('posts as p')
            ->select(
                'p.id',
                'p.text',
                'p.created_by as post_creator',
                'p.created_at',
                'p.location'
            )
            ->leftJoin('users as u', 'u.id', 'p.created_by')
            ->where('p.id', $id)
            ->whereNull('p.deleted_at')
            ->with('photos')
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }


        //Like
        $likedByMe = PostLike::query()
            ->where('post_id',$id)
            ->where('user_id', $request->user()->id)
            ->first();

        $post->likeByMe = $likedByMe?->like;
        $post->reactionByMe = $likedByMe?->reaction_id;

        $likeCount = PostLike::query()
            ->select(
                DB::raw("SUM(`like`) as like_count")
            )
            ->where('post_id', $id)
            ->first();

        $post->like_count = (int)$likeCount?->like_count;

        return response([
            'message' => 'Post retrieved',
            'data' => $post
        ], 200);

    }


    public function update($id, PostRequest $request)
    {
        $post = Post::query()
            ->where('id', $id)
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $post->update([
            'text' => $request->text,
            'created_by' => $request->created_by,
            'location' => $request->location
        ]);

        PostPhoto::query()
            ->where('post_id', $id)
            ->delete();

        foreach ($request->photos ?? [] as $photo) {
            $fileName = time() . rand(1, 1000) . '.' . $photo->extension();
            $fileNameWithUpload = 'storage/uploads/posts/' . $fileName;

            $photo->storeAs('public/uploads/posts/', $fileName);

            PostPhoto::query()
                ->create([
                    'post_id' => $post->id,
                    'photo' => $fileNameWithUpload
                ]);
        }

        return response([
            "message" => "Post updated",
            "data" => null
        ],200);
    }

    public function delete($id)
    {
        $post = Post::query()
            ->where('id', $id)
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $post->update(['deleted_at' => now()]);

        return response([
            "message" => "Post deleted",
            "data" => null
        ],200);

    }

    public function like($id, LikeRequest $request)
    {
        $post = Post::query()
            ->where('id',$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $checkUserLIke = PostLike::query()
            ->where('user_id',$request->user()->id)
            ->where('post_id',$id)
            ->first();

        if (!$checkUserLIke){
            PostLike::query()
                ->create([
                    'user_id' => $request->user()->id,
                    'post_id' => $id,
                    'reaction_id' => $request->reaction_id,
                    'like' => 1 //$request->like
                ]);
        }
        elseif($checkUserLIke->reaction_id == $request->reaction_id){
            $checkUserLIke->delete();
        }
        else {
            PostLike::query()
                ->where([
                    'user_id' => $request->user()->id,
                    'post_id' => $id,
                ])
                ->update([
                    'reaction_id' => $request->reaction_id,
                ]);
        }

        return response([
            'message' => 'Post liked',
            'data' => null
        ], 201);
    }

    public function userPosts(Request $request)
    {
        $userPosts = Post::query()
            ->from('posts as p')
            ->select(
                'p.id',
                'p.text',
                'p.created_by',
                'p.created_at',
                'p.location',
            )
            ->leftJoin('users as u', 'u.id', 'p.created_by')
            ->where('p.created_by', $request->user_id)
            ->whereNull('deleted_at')
            ->with('photos')
            ->orderByDesc('created_at')
            ->get();

        return response([
            "message" => " User Posts retrieved successfully",
            "data" => [
                'posts' => $userPosts
            ]
        ], 200);

    }


    public function archive($id, Request $request)
    {
        $post = Post::query()
            ->where('id', $id)
            ->where('created_by', $request->user()->id)
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $post->update([
            'is_archived' => date('Y-m-d H:i:s')
        ]);

        return response([
            'message' => 'Post archived',
            'data' => null
        ], 200);
    }

    public function archivedBack($id,Request $request)
    {
        $post = Post::query()
            ->where('id', $id)
            ->where('created_by', $request->user()->id)
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $post->update([
            'is_archived' => null
        ]);

        return response([
            'message' => 'Post archived back',
            'data' => null
        ], 200);
    }

    public function archive2($id, Request $request)
    {
        $post = Post::query()
            ->where('id',$id)
            ->where('created_by', $request->user()->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        #1ci yol
        if($post->is_archived){
            $post->update([
                'is_archived' => null
            ]);
        }
        else {
            $post->update([
                'is_archived' => now()
            ]);
        }

        #2ci yol
        $post->update([
            'is_archived' => $post->is_archived ? null : now()
        ]);

    }

    public function indexArchived(Request $request)
    {
        $posts = Post::query()
            ->from('posts as p')
            ->select(
                'p.id',
                'p.text',
                'p.created_by',
                'p.created_at',
                'p.location',
            )
            ->leftJoin('users as u', 'u.id', 'p.created_by')
            ->whereNull('deleted_at')
            ->where('created_by', $request->user()->id)
            ->whereNotNull('is_archived')
            ->with('photos')
            ->orderByDesc('created_at')
            ->get();

        return response([
            "message" => "Archived posts retrieved successfully",
            "data" => [
                'posts' => $posts
            ]
        ], 200);
    }

    public function saveAndUnSave($id, Request $request)
    {
        $post = Post::query()
            ->where('id',$id)
            ->whereNull('is_archived')
            ->whereNull('deleted_at')
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $checkSave = SavePost::query()
            ->where('user_id',$request->user()->id)
            ->where('post_id',$id)
            ->first();

        if (!$checkSave){
            SavePost::query()
                ->create([
                    'user_id' => $request->user()->id,
                    'post_id' => $id
                ]);
        }
        else{
            $checkSave->delete();
        }

        return response([
            "message" => "Post saved successfully",
            "data" => [
                'posts' => $checkSave
            ]
        ], 200);
    }

    public function getSaved(Request $request)
    {
        $posts = Post::query()
            ->from('posts as p')
            ->select(
                'p.id',
                'p.text',
                'p.created_by',
                'p.created_at',
                'p.location',
            )
            ->leftJoin('users as u', 'u.id', 'p.created_by')
            ->whereNull('deleted_at')
            ->whereNotNull('is_archived')
            ->with('photos')
            ->orderByDesc('created_at')
            ->get();

        return response([
            "message" => "Saved posts retrieved successfully",
            "data" => [
                'posts' => $posts
            ]
        ], 200);
    }

}
