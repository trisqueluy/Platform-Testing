<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use App\OpenSession;
use Carbon\Carbon;

class OpenSessionAPIController extends AppBaseController
{
    public function __construct()
    {
        Carbon::setLocale('es');
    }
    
    public function get($token)
    {
        if(request()->ip() !== env('APP_IP')) return response()->json([ 'user' => 0 ]);
        
        $session = OpenSession::where('token', $token)
            ->where('active', true)
            ->first();
        
        if(!$session) return response()->json([ 'user' => 0 ]);
        if((new Carbon($session->expires, 'America/Montevideo'))->diffInSeconds(Carbon::now()) <= 0) return response()->json([ 'user' => 0 ]);
        
        return response()->json([ 'user' => $session->user_id ]);
    }
    
    public function set($token, $value)
    {
        if(request()->ip() !== env('APP_IP')) return response()->json([ 'user' => 0 ]);
        
        $session = OpenSession::where('token', $token)
            ->where('active', true)
            ->first();
        
        if(!$session) return response()->json([ 'user' => 0 ]);
        if((new Carbon($session->expires, 'America/Montevideo'))->diffInSeconds(Carbon::now()) <= 0) return response()->json([ 'user' => 0 ]);
        
        $session->active = ($value == "true")
            ? true
            : false;
        $session->save();
        
        $r = ($session->active)
            ? $session->user_id
            : 0;
        
        return response()->json([ 'user' => $r ]);
    }
}
