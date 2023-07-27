<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'created_by',
        'location',
        'is_archived'
    ];

    public function photos()
    {
        return $this->hasMany(PostPhoto::class, 'post_id', 'id');
    }
}
