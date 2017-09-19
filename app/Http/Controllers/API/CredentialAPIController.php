<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Timeline;

class CredentialAPIController extends AppBaseController
{
    public function users($id)
    {
        $cr = [
            'status' => '',
            'message' => '',
            'response' => null
        ];
        $cr = (object) $cr;
        
        if(request()->ip() !== env('APP_IP'))
            return response()->json($cr);

        if(!preg_match('/^[1-9][0-9]*$/', intval($id)))
        {
            $cr->status = 403;
            $cr->messsage = 'No se puede procesar la petición.';
            
            return response()->json($cr);
        }
                
        $user = User::find($id);
            
        if(count($user) == 0)
        {
            $cr->status = 401;
            $cr->message = 'No existe el usuario.';
            
            return response()->json($cr);
        }
                
        $cr->status = 200;
        $cr->message = 'OK';
        $cr->response = (object) [
            'username'  => $user->timeline->username,
            'email'     => $user->email,
            'avatar'    => $user->avatar,
            'role'      => $user->role,
            'sex'       => $user->sex,
            'about'     => $user->about
        ];
        
        return response()->json($cr);
    }
}
