<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\EmailSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailSyncController extends Controller
{
    public function __invoke(Request $request, EmailSyncService $sync): JsonResponse
    {
        $token = Setting::get('imap_sync_token');
        if (!$token || !hash_equals($token, (string) $request->query('token'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $results = $sync->syncAll();
        return response()->json(['ok' => true, 'results' => $results]);
    }
}
