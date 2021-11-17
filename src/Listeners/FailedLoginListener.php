<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin;

class FailedLoginListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Failed $event): void
    {
        if ($event->user) {
            $user = $event->user;
            // Check if the model is loggable
            if (!method_exists($user, 'isLoggable')){
                return ;
            }else{
                if (!$user->isLoggable()){
                    //dd('failed');
                    return ;
                }
            }

            $log = $user->authentications()->create([
                'ip_address' => $ip = $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'login_at' => now(),
                'login_successful' => false,
                'location' => config('authentication-log.notifications.new-device.location') ? optional(geoip()->getLocation($ip))->toArray() : null,
            ]);

            if (config('authentication-log.notifications.failed-login.enabled')) {
                $failedLogin = config('authentication-log.notifications.failed-login.template') ?? FailedLogin::class;
                $event->user->notify(new $failedLogin($log));
            }
        }
    }
}
