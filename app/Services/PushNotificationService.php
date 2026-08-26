<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);

        $this->webPush->setAutomaticPadding(false);
    }

    public function notifyUser(User $user, string $title, string $body, string $url = '/'): void
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'keys'            => [
                    'p256dh' => $sub->p256dh_key,
                    'auth'   => $sub->auth_key,
                ],
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        $staleEndpoints = [];

        foreach ($this->webPush->flush() as $report) {
            if (! $report->isSuccess()) {
                $statusCode = $report->getResponse()?->getStatusCode();
                // 410 Gone or 404 Not Found means the subscription is no longer valid
                if (in_array($statusCode, [404, 410], true)) {
                    $staleEndpoints[] = $report->getEndpoint();
                }
            }
        }

        if (! empty($staleEndpoints)) {
            PushSubscription::whereIn('endpoint', $staleEndpoints)->delete();
        }
    }

    public function notifyAssignedUser(?\App\Models\Event $event, string $action = 'created'): void
    {
        if (! $event->assigned_user_id) {
            return;
        }

        $user = User::find($event->assigned_user_id);
        if (! $user) {
            return;
        }

        $title = "JCCS Calendar — Event {$action}";
        $body  = "\"{$event->title}\"";

        if ($event->start_datetime) {
            $date  = \Carbon\Carbon::parse($event->start_datetime)->format('M j, g:i A');
            $body .= " · {$date}";
        }

        $this->notifyUser($user, $title, $body, "/events/{$event->id}/edit");
    }
}
