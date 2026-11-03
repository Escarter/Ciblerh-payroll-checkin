<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

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
    protected $redirectTo = RouteServiceProvider::EMPLOYEE_HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function authenticated(Request $request, $user)
    {
        if ($user->status) {
            auditLog(
                auth()->user(),
                'user_login',
                'web',
                'login_successful',
                null,
                [],
                [],
                [
                    'translation_key' => 'login_successful',
                    'translation_params' => ['ip' => $request->ip()],
                ]
            );
           

            if ($user->isAdminUser()) {
                // Prioritize admin/manager/supervisor roles over employee role
                flash(__('Welcome back! :user', ['user' => auth()->user()->name]))->success();
                return redirect()->route('portal.dashboard');
            } elseif ($user->hasRole('employee')) {
                if($user->isContractValid()){
                    auditLog(
                        auth()->user(),
                        'user_login',
                        'web',
                        'login_contract_expired',
                        null,
                        [],
                        [],
                        [
                            'translation_key' => 'login_contract_expired',
                            'translation_params' => ['ip' => $request->ip()],
                        ]
                    );
                    auth()->logout();
                    flash(__('common.contract_expired_contact_supervisor'))->error()->important();
                    return redirect()->back()->withInput($request->input());
                }else{
                    flash(__('common.welcome_back_user', ['user' => auth()->user()->name]))->success();
                    return redirect()->route('employee.dashboard');
                }
            }else{
                return redirect()->route('portal.dashboard');
            }
        } else {
            auditLog(
                auth()->user(),
                'user_login',
                'web',
                'login_account_banned',
                null,
                [],
                [],
                [
                    'translation_key' => 'login_account_banned',
                    'translation_params' => ['ip' => $request->ip()],
                ]
            );
            auth()->logout();
            flash(__('common.account_not_active'))->error()->important();
            return redirect()->back()->withInput($request->input());
        }
    }

    public function logout(Request $request)
    {
        auditLog(
            auth()->user(),
            'user_logout',
            'web',
            'logout_successful',
            null,
            [],
            [],
            [
                'translation_key' => 'logout_successful',
                'translation_params' => ['ip' => $request->ip()],
            ]
        );

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('login');
    }
}
