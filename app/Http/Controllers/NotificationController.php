<?php

namespace App\Http\Controllers;

use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checkCensored();
    }
    
    protected function checkCensored()
    {
        $messages['not_contains'] = ':attribute no debe contener palabras prohibidas';
        if($this->request->method() == 'POST') {
            // Adjust the rules as needed
            $this->validate($this->request, 
                [
                  'name'          => 'not_contains',
                  'about'         => 'not_contains',
                  'title'         => 'not_contains',
                  'description'   => 'not_contains',
                  'tag'           => 'not_contains',
                  'email'         => 'not_contains',
                  'body'          => 'not_contains',
                  'link'          => 'not_contains',
                  'address'       => 'not_contains',
                  'website'       => 'not_contains',
                  'display_name'  => 'not_contains',
                  'key'           => 'not_contains',
                  'value'         => 'not_contains',
                  'subject'       => 'not_contains',
                  'username'      => 'not_contains',
                  'email'         => 'email',
                ],$messages);
        }
    }

    public function redirectNotification($username, $id)
    {
        $this->middleware('editOwn');

        $notification = Notification::findOrFail($id);

        $notification->seen = 1;
        $notification->save();
        
        // Modificacion
        
        if($notification->post_id != NULL) {
            return redirect('/#post'.$notification->post_id);
        }
        
        if ($notification->link != NULL) {
            return redirect(url($notification->link));
        }
        
        if($notification->notified_by != NULL) {
            $user = \App\User::find($notification->notified_by);
            if($user) {
                $timeline = $user->timeline;
                if($timeline) {
                    return redirect('/'.$user->timeline->username);
                }
            }
        }

        return redirect(url('/'));
    }

    public function markAllRead()
    {
        $notifications = Notification::where('user_id', Auth::user()->id)->with('notified_from')->update(['seen' => 1]);
    }
}
