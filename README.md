# Faffless Communication Channel

Laravel notification channel and client helpers for applications that send email, messages, commands, and verification requests through the Faffless Communication Hub service.

## Install

```bash
composer require faffless/communication-channel
php artisan faffless-channel:install \
    --app-id=msg_example
```

To pin a release series, require a constraint such as `faffless/communication-channel:^0.2`.

The install command publishes `config/faffless-hub.php` and adds the required credential keys to `.env` when they are missing:

```dotenv
FAFFLESS_HUB_APP_ID=msg_example
FAFFLESS_HUB_APP_SECRET=secret-from-communication-hub
```

Pass `--url` during installation only for local or staging hubs; otherwise the package uses its built-in production URL.

To route Laravel mail through the communication hub, set the default mailer:

```dotenv
MAIL_MAILER=faffless
```

The package registers a `faffless` mail transport and the install command adds this mailer to `config/mail.php` when it is missing:

```php
'faffless' => [
    'transport' => 'faffless',
    'url' => env('FAFFLESS_HUB_URL', 'https://communication.faffless.ai'),
    'app_id' => env('FAFFLESS_HUB_APP_ID'),
    'secret' => env('FAFFLESS_HUB_APP_SECRET'),
    'timeout' => 10,
],
```

With `MAIL_MAILER=faffless`, normal Laravel mail, mail notifications, and Laravel's built-in email verification emails are delivered through the Faffless Communication Hub.

Brand and provider routing are resolved by the hub from the authenticated application, so calling apps do not need brand-specific environment keys.

## Usage

```php
use Illuminate\Notifications\Notification;
use Faffless\CommunicationChannel\FafflessHubChannel;
use Faffless\CommunicationChannel\FafflessHubEmail;
use Faffless\CommunicationChannel\FafflessHubMessage;

class HireReadyNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [FafflessHubChannel::class];
    }

    public function toCommunicationHub(object $notifiable): FafflessHubMessage
    {
        return FafflessHubEmail::message(
            to: $notifiable->email,
            subject: 'Hire update',
            text: 'Your hire is ready.'
        )->purpose('hire_update')
            ->metadata(['user_id' => $notifiable->id]);
    }
}
```

For interactive phone-number verification flows, use the request/response client instead of the notification channel:

```php
use Faffless\CommunicationChannel\FafflessHubVerification;

$verifications = app(FafflessHubVerification::class);

$verification = $verifications->start('sms', $user->phone, [
    'purpose' => 'login',
]);

$result = $verifications->check(
    verificationId: $verification['verification_id'],
    code: $request->input('code')
);

$verifications->next($verification['verification_id']);
$verifications->retryVia($verification['verification_id'], 'call');
```

Transactional SMS:

```php
public function toCommunicationHub(object $notifiable): FafflessHubMessage
{
    return FafflessHubMessage::sms($notifiable->phone, 'Your hire is ready.')
        ->purpose('hire_update');
}
```

WhatsApp:

```php
public function toCommunicationHub(object $notifiable): FafflessHubMessage
{
    return FafflessHubMessage::whatsapp($notifiable->phone, 'Your hire is ready.')
        ->purpose('hire_update');
}
```

Tracker command:

```php
use Faffless\CommunicationChannel\FafflessHubCommand;

public function toCommunicationHub(object $notifiable): FafflessHubMessage
{
    return FafflessHubCommand::tracker(
        deviceId: 123,
        type: 'custom',
        attributes: ['data' => 'getstatus']
    )->purpose('tracker_command');
}
```

Tracker commands can also be sent directly:

```php
app(FafflessHubCommand::class)->sendTracker(
    deviceId: 123,
    type: 'custom',
    attributes: ['data' => 'getstatus'],
    payload: ['purpose' => 'tracker_command']
);
```
