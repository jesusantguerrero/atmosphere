<?php

namespace App\Notifications;

use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for every Loger notification.
 *
 * The canonical content of a notification lives in `toArray()` and is shared
 * shape across all channels: `{ message, cta, link }` (plus per-type extras
 * the in-app UI reads). This base derives the email and push representations
 * from that same array, so a subclass only has to implement `toArray()` to be
 * deliverable on every channel — no per-class mail/push boilerplate.
 *
 * Channel selection (`via`) is centralised here:
 *   - `database` is always on (the in-app inbox is the source of truth).
 *   - `mail` and OneSignal `push` are opt-in per user, read from the
 *     notifiable\'s `notification_prefs` (defaults: both ON).
 *   - Subclasses that also want Telegram/WhatsApp override `extraChannels()`.
 */
class LogerNotification extends Notification
{
    /**
     * Channels this notification uses in addition to the prefs-gated
     * mail/push and the always-on database channel. Override in subclasses
     * (e.g. return [TelegramChannel::class]).
     *
     * @return array<int, string>
     */
    protected function extraChannels($notifiable): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $channels = array_merge(['database'], $this->extraChannels($notifiable));

        if ($this->notifiableWants($notifiable, 'email') && $this->mailConfigured()) {
            $channels[] = 'mail';
        }

        if ($this->notifiableWants($notifiable, 'push')) {
            $channels[] = OneSignalChannel::class;
        }

        return array_values(array_unique($channels));
    }

    /**
     * Guard so the mail channel is only ever attempted when a from-address is
     * actually configured. Without this, an env with `MAIL_FROM_ADDRESS=null`
     * would throw an RFC-2822 error mid-notify — and because notifications
     * send synchronously (queue=sync, not ShouldQueue), that exception would
     * bubble into the originating request/job (e.g. an import). Fail soft:
     * skip email instead of breaking the flow.
     */
    protected function mailConfigured(): bool
    {
        return ! empty(config('mail.from.address'));
    }

    /**
     * Whether the notifiable has opted into a given optional channel
     * (`email` | `push`). Defaults to true when the notifiable predates the
     * preference API, so behaviour is opt-out, not opt-in.
     */
    protected function notifiableWants($notifiable, string $channel): bool
    {
        if (method_exists($notifiable, 'wantsNotificationChannel')) {
            return $notifiable->wantsNotificationChannel($channel);
        }

        return true;
    }

    /**
     * Build a consistent email from the canonical `toArray()` payload:
     * greeting, the message body, and a single call-to-action button that
     * deep-links into the app. Subclasses generally never need to override.
     */
    public function toMail($notifiable): MailMessage
    {
        $data = $this->safeToArray($notifiable);
        $message = $data['message'] ?? '';
        $cta = $data['cta'] ?? null;
        $link = $data['link'] ?? null;

        $mail = (new MailMessage)
            ->subject($cta ?: config('app.name', 'Loger'))
            ->greeting(__('Hi :name', ['name' => $notifiable->name ?? '']))
            ->line($message);

        if ($cta && $link) {
            $mail->action($cta, $this->absoluteLink($link));
        }

        return $mail->line(__('You are receiving this because notifications are enabled in your Loger preferences.'));
    }

    /**
     * Default OneSignal push payload derived from `toArray()`. The heading is
     * the CTA (a short action label) and the body is the message. Returns an
     * empty array when there is nothing meaningful to push, which the channel
     * treats as a no-op.
     *
     * @return array{heading?: string, message?: string, url?: string}
     */
    public function toOneSignal($notifiable): array
    {
        $data = $this->safeToArray($notifiable);
        $message = $data['message'] ?? '';

        if ($message === '') {
            return [];
        }

        $payload = [
            'heading' => $data['cta'] ?? config('app.name', 'Loger'),
            'message' => $message,
        ];

        if (! empty($data['link'])) {
            $payload['url'] = $this->absoluteLink($data['link']);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeToArray($notifiable): array
    {
        if (method_exists($this, 'toArray')) {
            $data = $this->toArray($notifiable);

            return is_array($data) ? $data : [];
        }

        return [];
    }

    private function absoluteLink(string $link): string
    {
        if (str_starts_with($link, 'http://') || str_starts_with($link, 'https://')) {
            return $link;
        }

        return url($link);
    }

    public function toTelegram($notifiable)
    {
        return [];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toWhatsapp($notifiable)
    {
        return [];
    }
}
