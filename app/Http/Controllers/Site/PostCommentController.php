<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\CommentLike;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Comment;

class PostCommentController extends Controller
{
    public function index($id, Request $request)
    {
        $post = Post::query()
            ->select([
                'created_by'
            ])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        $limit = $request->limit ?? 10;
        $page = $request->page ?? 1;

        $offset = $limit * ($page - 1);

        $isCreatedByMe = $post->created_by == $request->user()->id ? 1 : 0;

        $comments = PostComment::query()
            ->from('post_comments as pc')
            ->select([
                'pc.id',
                'pc.comment',
                'pc.parent_id',
                'pc.created_at',
                'u.name as user_name',
                 DB::raw(
                     "CASE WHEN pc.user_id = ". $request->user()->id. " THEN 1 ELSE 0 END as can_edit"
                 ),
                DB::raw(
                     "CASE WHEN $isCreatedByMe = 1 THEN 1 WHEN pc.user_id = {$request->user()->id} THEN 1 ELSE 0 END as can_delete"
                )
            ])
            ->whereNull('parent_id')
            ->leftJoin('users as u','u.id','pc.user_id')
            ->with('children')
            ->where('pc.post_id', $id);

        $total = $comments->count();

        $comments = $comments->limit($limit)->offset($offset)
            ->get();

        return response([
            'message' => "Comment created",
            'data' => [
                'total' => $total,
                'comments' => $comments,
            ]
        ]);

    }
    public function store($id, Request $request)
    {
        $post = Post::query()
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$post) {
            return response([
                'message' => "Post not found",
                'data' => null
            ], 404);
        }

        PostComment::query()
            ->create([
                'user_id' => $request->user()->id,
                'post_id' => $id,
                'comment' => $request->comment,
                'parent_id' => $request->parent_id
            ]);

        return response([
            'message' => "Comment created",
            'data' => null
        ]);
    }

    public function delete($id, Request $request)
    {
        $comment = PostComment::query()
            ->from('post_comments as pc')
            ->leftJoin('posts as p','p.created_by','pc.user_id')
            ->where("pc.id", $id)
            ->where(function ($q) use($request) { #Bu functiona deyilir : callback function, anonim function, closure
                return $q->where("pc.user_id", $request->user()->id)
                    ->orWhere('p.created_by', $request->user()->id);
            })
            ->first();

        /*
         * SELECT * FROM post_comments as pc
         * left join posts as p on p.created_by = pc.user_id
         * where pc.id = $id and
         * (pc.user_id = $request->user()->id or p.created_by = $request->user()->id)
         */

        if (!$comment) {
            return response([
                'message' => "Comment not found",
                'data' => null
            ], 404);
        }

        $comment->delete();
    }

    public function like($id, Request $request)
    {
        $comment = PostComment::query()
            ->where('id', $id)
            ->first();

        if (!$comment) {
            return response([
                'message' => "Comment not found",
                'data' => null
            ], 404);
        }

        $checkUserLikeOrDislike = CommentLike::query()
            ->where('user_id', $request->user()->id)
            ->where('comment_id', $id)
            ->first();

        if (!$checkUserLikeOrDislike) {
            CommentLike::query()
                ->create([
                    'user_id' => $request->user()->id,
                    'comment_id' => $id,
                    'is_like' => $request->is_like
                ]);
        } elseif ($checkUserLikeOrDislike->is_like == $request->is_like) {
            $checkUserLikeOrDislike->delete();
        } else {
            $checkUserLikeOrDislike->update([
                'is_like' => $request->is_like
            ]);
        }

        return response([
            'message' => 'Comment liked',
            'data' => null
        ], 201);
    }
}
