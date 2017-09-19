<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OpenSession extends Model
{
    public $table = 'open_sessions';
    
    protected $fillable = [
        'token', 'expires', 'referrer', 'user_id', 'active'
    ];
    
    public function user()
    {
        return $this->hasOne('App\User', 'user_id');
    }
}
