<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function redirectToProvider(string $provider)
    {
        return \Socialite::driver($provider)->with(['bot_prompt'=>'aggressive'])->redirect();
    }

    public function handleProviderCallback(\App\SocialAccountService $accountService, string $provider)
    {
        try {
            $user = \Socialite::with($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login');
        }

        $authUser = $accountService->findOrCreate(
            $user,
            $provider
        );
        auth()->login($authUser, true);
        return redirect()->to('/home');
    }
}
