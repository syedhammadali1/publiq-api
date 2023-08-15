<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

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
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'firstname' => 'required|regex:/^[a-z]+$/i',
            'lastname' => 'required|regex:/^[a-z]+$/i',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|unique:users,name',
            'password' => 'required|string|min:8|confirmed',
            'country' => 'required',
            // 'age' => 'required|max:2',
            'dob' => 'required|date',
            'gender' => 'required',
            // 'type' => 'required',
            'terms' => 'required',
            'question_2' => 'required',
            'g-recaptcha-response' => 'required|captcha',
            'additional_comments' => 'nullable|min:10|max:250',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'email' => $data['email'],
            'name' => $data['username'],
            'password' => Hash::make($data['password']),
            // 'type' => $data['type'],
        ]);
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 201)
            : redirect($this->redirectPath());
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }



    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        UserDetails::create([
            'user_id' => $user->id,
            'first_name' => $request->firstname,
            'last_name' => $request?->lastname,
            'country' => $request->country,
            'additional_comments' => @$request->additional_comments,
            // 'age' => $request->age,
            'birthday' => $request->dob,
            'gender' => @$request->gender,
            'from' => json_encode(@$request->question_2),
        ]);
        $user->createToken('App')->accessToken;
    }
}
