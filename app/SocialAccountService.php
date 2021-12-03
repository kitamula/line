<?php

namespace App;

use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccountService
{
    public function findOrCreate(ProviderUser $providerUser, $provider)
    {
        // linked_social_accountsへすでにユーザ登録済みかチェック
        $account = LinkedSocialAccount::where('provider_name', $provider)
                   ->where('provider_id', $providerUser->getId())
                   ->first();
        if ($account) {
            // すでにユーザ登録済みの場合はusersテーブルの情報を返す
            return $account->user;
        }

        // SNSサイトから渡されたemailですでにユーザ作成済みかチェック
        $user = User::where('email', $providerUser->getEmail())->first();
        if (!$user) {
            // 未作成ならここで作成する
            $user = User::create([
                'email' => $providerUser->getEmail(),
                'name'  => $providerUser->getName(),
            ]);
        }

        // 取得(or作成)したusersテーブルに紐づくlinked_social_accountsのレコードを1行追加
        $user->accounts()->create([
            'provider_id'   => $providerUser->getId(),
            'provider_name' => $provider,
        ]);

        // 取得したusersテーブルの情報を返す
        return $user;
    }
}
