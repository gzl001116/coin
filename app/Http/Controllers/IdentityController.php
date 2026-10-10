<?php

namespace App\Http\Controllers;

use App\Support\SiteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function show(Request $request, SiteContext $sites): JsonResponse
    {
        // SiteContext checks that this OAuth token belongs to an active, registered site
        // and verifies marketplace.access. Do not return wallet balance or transaction history here.
        $site = $sites->requireScope($request->user(), 'marketplace.access');
        $user = $request->user();

        return response()->json([
            'id' => (string) $user->getKey(),
            'name' => (string) ($user->name ?? '用户'),
            'site_id' => (string) $site->getKey(),
        ]);
    }
}
