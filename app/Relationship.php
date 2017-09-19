<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    public $table = 'relationships';
    
    protected $fillable = [
        'id',
        'avatar',
        'cover',
        'description',
        'user1_id',
        'user2_id',
        'user1_type',
        'user2_type',
        'verified',
    ];
}
