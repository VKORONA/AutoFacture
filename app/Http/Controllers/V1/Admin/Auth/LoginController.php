<?php

namespace Crater\Http\Controllers\V1\Admin\Auth;

use Crater\Http\Controllers\Controller;
use Crater\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    /**
     * Keep the short "admin" identifier limited to local and test environments.
     * The real email remains stored on the account for notifications and resets.
     */
    protected function credentials(Request $request): array
    {
        $identifier = trim((string) $request->input($this->username()));

        if (app()->environment(['local', 'testing']) && strcasecmp($identifier, 'admin') === 0) {
            $identifier = 'admin@autofacture.local';
        }

        return [
            $this->username() => $identifier,
            'password' => (string) $request->input('password'),
        ];
    }
}
