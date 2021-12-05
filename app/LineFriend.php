<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LineFriend extends Model
{
    protected $guarded = ['id'];

    public function linkedSocialAccount()
    {
        return $this->hasOne(LinkedSocialAccount::class, 'provider_id', 'provider_id');
    }

    public static function findByProviderId($provider_id)
    {
        return self::where('provider_id', $provider_id)->first();
    }
}
