<?php

namespace App\Http\Middleware;

use Closure;
use App\OpenSession;

class OpenSesion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = isset($request->user()->id) 
            ? $request->user()->id 
            : null;
        $active = (!strpos($request->url(), 'logout')) 
            ? true 
            : false;
        
        $session = $this->open_session(
            $request->url(),
            $user,
            $active
        );
        $cookie = cookie(
            'trisquel_sessid',
            $session->token,
            \Carbon\Carbon::now()->addDays(1)->toDateTimeString(),
            '/',
            'trisquel.com.uy',
            false, // cambiar esto a verdadero cuando se pase a produccion
            true
        );
        
        return $next($request)->cookie($cookie);
    }
    
    private function open_session($referrer, $user, $active)
    {
        if(count(request()->cookie('trisquel_sessid')) === 99)
        {
            $os = OpenSession::where('token', request()->cookie('trisquel_sessid'))->first();
        }
        else
        {
            $os = new OpenSession;
        }
        
        $os->token = str_random(99);
        $os->expires = \Carbon\Carbon::now()->toDateTimeString();
        $os->referrer = $referrer;
        $os->user_id = $user;
        $os->active = $active;
        $os->save();
        
        return $os;
    }
}
