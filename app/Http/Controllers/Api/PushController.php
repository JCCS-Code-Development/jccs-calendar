<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function publicKey()
    {
        return response()->json(['publicKey' => config('services.vapid.public_key')]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint'        => ['required', 'string'],
            'keys.p256dh'     => ['required', 'string'],
            'keys.auth'       => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id'  => auth()->id(),
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_key'   => $request->input('keys.auth'),
            ]
        );

        return response()->json(['subscribed' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $request->validate(['endpoint' => ['required', 'string']]);

        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['unsubscribed' => true]);
    }
}
