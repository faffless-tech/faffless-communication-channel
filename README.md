# RPOS Messaging Channel

Laravel notification channel for applications that send email and verification requests through the RPOS Messaging service.

## Install

```bash
composer require rpos/messaging-channel
php artisan messaging-channel:install \
    --url=https://messaging.example.com \
    --app-id=msg_example
```

To pin a release series, require a constraint such as `rpos/messaging-channel:^0.2`.

The install command publishes `config/messaging-channel.php` and adds these keys to `.env` when they are missing:

```dotenv
MESSAGING_SERVICE_URL=https://messaging.example.com
MESSAGING_APP_ID=msg_example
MESSAGING_APP_SECRET=secret-from-messaging-service
MESSAGING_SERVICE_TIMEOUT=10
MESSAGING_BRAND_KEY="${VITE_BRAND_KEY}"
MESSAGING_BRAND_NAME="${VITE_BRAND_NAME}"
MESSAGING_BRAND_REGION="${VITE_BRAND_REGION}"
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
    'brand_key' => env('MESSAGING_BRAND_KEY', env('VITE_BRAND_KEY')),
    'brand_name' => env('MESSAGING_BRAND_NAME', env('VITE_BRAND_NAME')),
    'brand_region' => env('MESSAGING_BRAND_REGION', env('VITE_BRAND_REGION')),
],
```

With `MAIL_MAILER=messaging_hub`, normal Laravel mail, mail notifications, and Laravel's built-in email verification emails are delivered through the RPOS Messaging Hub.

The package includes brand context on mail transport and notification channel requests. By default it reads from `MESSAGING_BRAND_*` and falls back to the calling app's `VITE_BRAND_*` values:

```dotenv
VITE_BRAND_KEY=hr
VITE_BRAND_NAME="Handy Rentals"
VITE_BRAND_REGION=au
```

The hub uses `brand_key` and `brand_region` to resolve the correct provider account while keeping provider credentials out of calling apps.

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

Transactional SMS:

```php
public function toMessagingService(object $notifiable): MessagingMessage
{
    return MessagingMessage::sms($notifiable->phone, 'Your hire is ready.')
        ->purpose('hire_update');
}
```

WhatsApp:

```php
public function toMessagingService(object $notifiable): MessagingMessage
{
    return MessagingMessage::whatsapp($notifiable->phone, 'Your hire is ready.')
        ->purpose('hire_update');
}
```
