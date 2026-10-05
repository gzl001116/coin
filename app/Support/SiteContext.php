<?php
namespace App\Support;

use App\Models\Site;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Passport\Token;

class SiteContext
{
    public function fromToken($user): Site
    {
        $token = $user->token();
        if (!$token instanceof Token) {
            throw new AuthorizationException('INVALID_ACCESS_TOKEN');
        }
        $site = Site::query()->where('client_id', $token->client_id)->where('status', 'active')->first();
        if (!$site) {
            throw new AuthorizationException('SITE_NOT_ACTIVE');
        }
        return $site;
    }

    public function requireScope($user, string $scope): Site
    {
        $site = $this->fromToken($user);
        if (!$user->tokenCan($scope)) {
            throw new AuthorizationException('INSUFFICIENT_SCOPE');
        }
        return $site;
    }
}
