<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'post_id', 'comment' ,'parent_id'
    ];

    public function children()
    {
        return $this->hasMany(PostComment::class,'parent_id','id')
            ->select("id", "comment", "parent_id", 'created_at')
            ->with("children");
    }
}
