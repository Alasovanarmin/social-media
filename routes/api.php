<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\UserController;
use App\Http\Controllers\Site\PostController;
use App\Http\Controllers\Site\PostLikeController;
use App\Http\Controllers\Site\PostCommentController;
use App\Http\Controllers\Site\FollowerController;


Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::group(["middleware" => 'auth:sanctum'], function () {
    #Site users
    Route::get("/profile", [UserController::class, 'profile']);
    Route::post("/profile/update", [UserController::class, 'profileUpdate']);

    #Post
    Route::post("/post", [PostController::class, 'store']);
    Route::get("/posts", [PostController::class, 'index']);
    Route::get("/post/{id}", [PostController::class, 'show']);
    Route::post("/post/{id}/update", [PostController::class, 'update']);
    Route::delete("/post/{id}", [PostController::class, 'delete']);

    //User's Posts
    Route::get("/userPosts", [PostController::class, 'userPosts']);

    #Post like
    Route::post('/post/{id}/like', [PostController::class, 'like']);

    #Post's comment
    Route::get('/post/{id}/comments', [PostCommentController::class, 'index']);
    Route::post('/post/{id}/comment', [PostCommentController::class, 'store']);
    Route::delete('/post/comment/{id}', [PostCommentController::class, 'delete']);
    Route::post('/post/comment/{id}/like', [PostCommentController::class, 'like']);

    Route::post('/post/{id}/archive', [PostController::class, 'archive']);
    Route::post('/post/{id}/archivedBack', [PostController::class, 'archivedBack']);
    Route::post('/post/{id}/archive2', [PostController::class, 'archive2']);
    Route::get('/post/archived', [PostController::class, 'indexArchived']);

    Route::post('/post/{id}/save', [PostController::class, 'saveAndUnSave']);
    Route::get('/post/{id}/savedPosts', [PostController::class, 'getSaved']);

    #Follow
    Route::post('/follow', [FollowerController::class, 'follow']);
    Route::get('/followers', [FollowerController::class, 'followers']);
    Route::get('/followings', [FollowerController::class, 'followings']);
    Route::get('/profile/follow/info', [FollowerController::class, 'userInfo']);



});
