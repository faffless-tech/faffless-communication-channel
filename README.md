# RPOS Messaging Channel

Laravel notification channel for applications that send email and verification requests through the RPOS Messaging service.

## Install

```bash
composer require rpos/messaging-channel:^0.1
php artisan messaging-channel:install \
    --url=https://messaging.example.com \
    --app-id=msg_example
```

The install command publishes `config/messaging-channel.php` and adds these keys to `.env` when they are missing:

```dotenv
MESSAGING_SERVICE_URL=https://messaging.example.com
MESSAGING_APP_ID=msg_example
MESSAGING_APP_SECRET=secret-from-messaging-service
MESSAGING_SERVICE_TIMEOUT=10
```

To route Laravel mail through the messaging hub, set the default mailer:

```dotenv
MAIL_MAILER=messaging_hub
```

The package registers a `messaging_hub` mail transport and the install command adds this mailer to `config/mail.php` when it is missing:

```php
'messaging_hub' => [
    'transport' => 'messaging_hub',
    'url' => env('MESSAGING_SERVICE_URL'),
    'app_id' => env('MESSAGING_APP_ID'),
    'secret' => env('MESSAGING_APP_SECRET'),
    'timeout' => env('MESSAGING_SERVICE_TIMEOUT', 10),
],
```

With `MAIL_MAILER=messaging_hub`, normal Laravel mail, mail notifications, and Laravel's built-in email verification emails are delivered through the RPOS Messaging Hub.

## Usage

```php
use Illuminate\Notifications\Notification;
use Rpos\MessagingChannel\MessagingChannel;
use Rpos\MessagingChannel\MessagingMessage;

class HireReadyNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [MessagingChannel::class];
    }

    public function toMessagingService(object $notifiable): MessagingMessage
    {
        return MessagingMessage::email(
            to: $notifiable->email,
            subject: 'Hire update',
            text: 'Your hire is ready.'
        )->purpose('hire_update')
            ->metadata(['user_id' => $notifiable->id]);
    }
}
```

Phone verification request:

```php
public function toMessagingService(object $notifiable): MessagingMessage
{
    return MessagingMessage::verification('phone', $notifiable->phone)
        ->purpose('login');
}
```
