<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Flash;
use App\Relationship;
use App\Setting;
use App\Timeline;
use App\User;
use Validator;
use Teepluss\Theme\Facades\Theme;

class RelationshipController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'user1_id' => 'required',
            'user1_type' => 'required',
            'user2_id' => 'required',
        ]);
        
        $relationship = new Relationship;
        
        $relationship->user1_id = $request->user1_id;
        $relationship->user1_type = $request->user1_type;
        $relationship->user2_id = $request->user2_id;
        $relationship->verified = false;
        
        $relationship->save();
        
        if(!$relationship->id)
        {
            Flash::error('No se pudo crear la relación');
            
            return redirect()->back();
        }
        
        Flash::success('Relación creada. Esperando aprobación.');
        
        return redirect(Auth::user()->username . '/settings/relationships/' . $relationship->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($user, $id)
    {
        $relationship = Relationship::find($id);
        
        if(!$relationship) return redirect()->back()->with('Relación no encontrada');
        
        $user = User::find($relationship->user1_id);
        $user2 = User::find($relationship->user2_id);
        
        $theme = Theme::uses(Setting::get('current_theme', 'default'))->layout('default');
        $theme->setTitle($user->name.' '.Setting::get('title_seperator').' Relaciones '.Setting::get('title_seperator').' '.Setting::get('site_title').' '.Setting::get('title_seperator').' '.Setting::get('site_tagline'));
        
        return $theme->scope('relationships/show', compact('user', 'user2', 'relationship'))->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($username, $id)
    {
        $r = Relationship::find($id);
        $u = User::find(Auth::id());
        
        // Si el usuario no es igual al usuario
        /*if($u->timeline->username !== $username)
            return redirect(Auth::user()->username . '/settings/relationships/');*/
        
        $r->delete();
        
        return redirect(Auth::user()->username . '/settings/relationships/');
            
    }
}
