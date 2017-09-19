<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Setting;
use App\Timeline;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Intervention\Image\Facades\Image;
use Laravel\Socialite\Facades\Socialite;
use Teepluss\Theme\Facades\Theme;
use Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, $captcha = null)
    {
        $messages = [
            'no_admin' => 'El nombre :attribute está restringido para uso administrativo'
        ];
        $rules = [
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|min:8|confirmed|alpha_dash',
            'gender'    => 'required',
            'username'  => 'required|max:25|min:6|alpha_dash|unique:timelines|no_admin',
            'affiliate' => 'exists:timelines,username',
            /* Custom Attributes */
            'interest'  => 'required|max:100',
            'role'     => 'required',
        ];

        if ($captcha) {
            $messages = [
                'g-recaptcha-response.required' => trans('messages.captcha_required'),
                
                'name.required'     => 'El nombre es obligatorio',
                'name.max'          => 'El nombre debe tener menos de 255 caracteres',
                'email.required'    => 'El email es obligatorio',
                'email.email'       => 'El email debe ser una direcci&oacute;n v&aacute;lida',
                'email.max'         => 'El email debe tener menos de 255 caracteres',
                'email.unique'      => 'El email ya existe',
                'password.required' => 'La contrase&ntilde;a es obligatoria',
                'password.min'      => 'La contraseña debe tener como m&iacute;nimo ocho caracteres',
                'password.alpha_dash'=> 'La contraseña debe tener letras, números o guiones',
                'password.confirmed'=> 'Las contraseñas no coinciden',
                'gender.required'   => 'El g&eacute;nero es obligatorio',
                'affiliate.exists'  => 'El afiliado no existe',
                'username.required' => 'El nombre de usuario es obligatorio',
                'username.max'      => 'El nombre de usuario debe tener como m&aacute;ximo 25 caracteres',
                'username.min'      => 'El nombre de usuario debe tener como m&iacute;nimo seis caracteres',
                'username.alpha_dash'=> 'El nombre de usuario debe contener letras, números o guiones',
                'username.unique'   => 'El nombre de usuario ya existe',
                'username.no_admin' => 'El nombre de usuario no debe contener la palabra "admin"',
                'interest.required' => 'El inter&eacute;s principal es oblitatorio',
                'interest.max'      => 'El inter&eacute;s principal debe tener como m&aacute;ximo cien caracteres',
                'role.required'     => 'El rol es obligatorio',
            ];
            $rules['g-recaptcha-response'] = 'required';
        }

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return User
     */
    protected function create(array $data)
    {
        $timeline = Timeline::create([
            'username' => $data['username'],
            'name'     => $data['name'],
        ]);

        return User::create([
            'email'       => $data['email'],
            'password'    => bcrypt($data['password']),
            'timeline_id' => $timeline->id,
        ]);
    }

    public function register()
    {
        if (Auth::user()) {
            return Redirect::to('/');
        }

        $theme = Theme::uses(Setting::get('current_theme', 'default'))->layout('guest');
        $theme->setTitle(trans('auth.register').' '.Setting::get('title_seperator').' '.Setting::get('site_title').' '.Setting::get('title_seperator').' '.Setting::get('site_tagline'));

        return $theme->scope('users.register')->render();
    }

    protected function registerUser(Request $request, $socialLogin = false)
    {
        if (Setting::get('captcha') == 'on' && !$socialLogin) {
            $validator = $this->validator($request->all(), true);
        } else {
            $validator = $this->validator($request->all());
        }

        if ($validator->fails()) {
            return response()->json(['status' => '201', 'err_result' => $validator->errors()->toArray()]);
        }

        if ($request->affiliate) {
            $timeline = Timeline::where('username', $request->affiliate)->first();
            $affiliate_id = $timeline->user->id;
        } else {
            $affiliate_id = null;
        }

        //Create timeline record for the user
        $timeline = Timeline::create([
            'username' => $request->username,
            'name'     => $request->name,
            'type'     => 'user',
            'about'    => ''
            ]);

        //Create user record
        $user = User::create([
            'email'             => $request->email,
            'password'          => bcrypt($request->password),
            'timeline_id'       => $timeline->id,
            'gender'            => $request->gender,
            'affiliate_id'      => $affiliate_id,
            'verification_code' => str_random(30),
            'remember_token'    => str_random(10),
            /* Custom Attributes */
            'interest'          => $request->interest,
            'interest2'         => $request->interest2,
            'role'              => $request->role,
            'sex'               => $request->sex,
        ]);
        if (Setting::get('birthday') == 'on' && $request->birthday != '') {
            $user->birthday = date('Y-m-d', strtotime($request->birthday));
            $user->save();
        }

        if (Setting::get('city') == 'on' && $request->city != '') {
            $user->city = $request->city;
            $user->save();
        }

        $user->name = $timeline->name;
        $user->email = $request->email;

        //saving default settings to user settings
        $user_settings = [
          'user_id'               => $user->id,
          'confirm_follow'        => Setting::get('confirm_follow'),
          'follow_privacy'        => Setting::get('follow_privacy'),
          'comment_privacy'       => Setting::get('comment_privacy'),
          'timeline_post_privacy' => Setting::get('user_timeline_post_privacy'),
          'post_privacy'          => Setting::get('post_privacy'),
          'message_privacy'       => Setting::get('user_message_privacy'), ];

        //Create a record in user settings table.
        $userSettings = DB::table('user_settings')->insert($user_settings);

        if ($user) {
            if ($socialLogin) {
                return $timeline;
            } else {
                $chk = '';
                if (Setting::get('mail_verification') == 'on') {
                    $chk = 'on';
                    Mail::send('emails.welcome', ['user' => $user], function ($m) use ($user) {
                        $m->from(Setting::get('noreply_email'), Setting::get('site_name'));

                        $m->to($user->email, $user->name)->subject('Bienvenido a '.Setting::get('site_name'));
                    });
                }

                return response()->json(['status' => '200', 'message' => trans('auth.verify_email'), 'emailnotify' => $chk]);
            }
        }
    }

    public function verifyEmail(Request $request)
    {
        $user = User::where('email', '=', $request->email)->where('verification_code', '=', $request->code)->first();

        if ($user->email_verified) {
            return Redirect::to('login')
            ->with('login_notice', trans('messages.verified_mail'));
        } elseif ($user) {
            $user->email_verified = 1;
            $user->update();
            return Redirect::to('login')
            ->with('login_notice', trans('messages.verified_mail_success'));
        } else {
            echo trans('messages.invalid_verification');
        }
    }

    public function facebookRedirect()
    {
        return Socialite::with('facebook')->redirect();
    }

    // to get authenticate user data
    public function facebook()
    {
        $facebook_user = Socialite::with('facebook')->user();

        $email = $facebook_user->email;

        if ($email == null) {
            $email = $facebook_user->id.'@facebook.com';
        }

        $user = User::firstOrNew(['email' => $email]);

        if ($facebook_user->name != null) {
            $name = $facebook_user->name;
        } else {
            $name = $email;
        }

        if (!$user->id) {
            $request = new Request(['username' => $facebook_user->id,
              'name'                           => $name,
              'email'                          => $email,
              'password'                       => bcrypt(str_random(8)),
              'gender'                         => 'none',
            ]);

            $timeline = $this->registerUser($request, true);
            //  Prepare the image for user avatar
            if ($facebook_user->avatar != null) {
                $avatar = Image::make($facebook_user->avatar);
                $photoName = date('Y-m-d-H-i-s').str_random(8).'.png';
                $avatar->save(storage_path().'/uploads/users/avatars/'.$photoName, 60);
                $media = Media::create([
                        'title'  => $photoName,
                        'type'   => 'image',
                        'source' => $photoName,
                      ]);
                $timeline->avatar_id = $media->id;
                $timeline->save();
            }

            $user = $timeline->user;
        } else {
            $timeline = $user->timeline;
        }


        if (Auth::loginUsingId($user->id)) {
            return redirect('/')->with(['message' => trans('messages.change_username_facebook'), 'status' => 'warning']);
        } else {
            return redirect($timeline->username)->with(['message' => trans('messages.user_login_failed'), 'status' => 'success']);
        }
    }

    public function googleRedirect()
    {
        return Socialite::with('google')->redirect();
    }

    // to get authenticate user data
    public function google()
    {
        $google_user = Socialite::with('google')->user();
        if (isset($google_user->user['gender'])) {
            $user_gender = $google_user->user['gender'];
        } else {
            $user_gender = 'none';
        }
        $user = User::firstOrNew(['email' => $google_user->email]);
        if (!$user->id) {
            $request = new Request(['username' => $google_user->id,
              'name'                           => $google_user->name,
              'email'                          => $google_user->email,
              'password'                       => bcrypt(str_random(8)),
              'gender'                         => $user_gender,
            ]);
            $timeline = $this->registerUser($request, true);

            //  Prepare the image for user avatar
            $avatar = Image::make($google_user->avatar);
            $photoName = date('Y-m-d-H-i-s').str_random(8).'.png';
            $avatar->save(storage_path().'/uploads/users/avatars/'.$photoName, 60);

            $media = Media::create([
                      'title'  => $photoName,
                      'type'   => 'image',
                      'source' => $photoName,
                    ]);

            $timeline->avatar_id = $media->id;

            $timeline->save();
            $user = $timeline->user;
        }

        if (Auth::loginUsingId($user->id)) {
            return redirect('/')->with(['message' => trans('messages.change_username_google'), 'status' => 'warning']);
        } else {
            return redirect($timeline->username)->with(['message' => trans('messages.user_login_failed'), 'status' => 'success']);
        }
    }

    public function twitterRedirect()
    {
        return Socialite::with('twitter')->redirect();
    }

  // to get authenticate user data
    public function twitter()
    {
        $twitter_user = Socialite::with('twitter')->user();

        $user = User::firstOrNew(['email' => $twitter_user->id.'@twitter.com']);
        if (!$user->id) {
            $request = new Request(['username'   => $twitter_user->id,
              'name'                           => $twitter_user->name,
              'email'                          => $twitter_user->id.'@twitter.com',
              'password'                       => bcrypt(str_random(8)),
              'gender'                         => 'none',
            ]);
            $timeline = $this->registerUser($request, true);
              //  Prepare the image for user avatar
            $avatar = Image::make($twitter_user->avatar_original);
            $photoName = date('Y-m-d-H-i-s').str_random(8).'.png';
            $avatar->save(storage_path().'/uploads/users/avatars/'.$photoName, 60);

            $media = Media::create([
                      'title'  => $photoName,
                      'type'   => 'image',
                      'source' => $photoName,
                    ]);

            $timeline->avatar_id = $media->id;

            $timeline->save();
            $user = $timeline->user;
        }

        if (Auth::loginUsingId($user->id)) {
            return redirect('/')->with(['message' => trans('messages.change_username_twitter').' <b>'.$user->email.'</b>', 'status' => 'warning']);
        } else {
            return redirect('login')->with(['message' => trans('messages.user_login_failed'), 'status' => 'error']);
        }
    }

    public function linkedinRedirect()
    {
        return Socialite::with('linkedin')->redirect();
    }

  // to get authenticate user data
    public function linkedin()
    {
        $linkedin_user = Socialite::with('linkedin')->user();

        $user = User::firstOrNew(['email' => $linkedin_user->email]);
        if (!$user->id) {
            $request = new Request(['username'   => preg_replace('/[^A-Za-z0-9 ]/', '', $linkedin_user->id),
              'name'                           => $linkedin_user->name,
              'email'                          => $linkedin_user->email,
              'password'                       => bcrypt(str_random(8)),
              'gender'                         => 'none',
            ]);

            $timeline = $this->registerUser($request, true);

              //  Prepare the image for user avatar
            $avatar = Image::make($linkedin_user->avatar_original);
            $photoName = date('Y-m-d-H-i-s').str_random(8).'.png';
            $avatar->save(storage_path().'/uploads/users/avatars/'.$photoName, 60);

            $media = Media::create([
                      'title'  => $photoName,
                      'type'   => 'image',
                      'source' => $photoName,
                    ]);

            $timeline->avatar_id = $media->id;

            $timeline->save();
            $user = $timeline->user;
        }

        if (Auth::loginUsingId($user->id)) {
            return redirect('/')->with(['message' => trans('messages.change_username_linkedin'), 'status' => 'warning']);
        } else {
            return redirect('login')->with(['message' => trans('messages.user_login_failed'), 'status' => 'error']);
        }
    }
}
