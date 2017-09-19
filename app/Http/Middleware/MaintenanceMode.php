<?php

namespace App\Http\Middleware;

use Closure;

class MaintenanceMode
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
        $data = array();
        $env = file_get_contents(base_path() . '/.env');
        $env = preg_split('/\r\n/', $env);
        $mode = $request->maintenance_mode;
        $write = false;
        
        foreach($env as $e)
        {
            if($e !== '')
            {
                $exp = explode('=', $e);
                $data[$exp[0]] = $exp[1];
            }
            
        }

        if($data['MAINTENANCE_MODE'] === 'on')
        {
            if(\Auth::check() == false)
                return view('errors.503');
        }
        return $next($request);
        
    }
}
