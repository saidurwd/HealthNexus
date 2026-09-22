<?php

namespace App\Services;

use App\Mail\SystemNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected array $templates = [
        'password_reset' => [
            'title' => 'Password Reset',
            'message' => 'You requested a password reset. Your reset token is :token.',
        ],
        'security_alert' => [
            'title' => 'Security Alert',
            'message' => 'A new sign-in was detected for your account on :device.',
        ],
        'appointment_reminder' => [
            'title' => 'Appointment Reminder',
            'message' => ':patient has an appointment scheduled on :date at :time.',
        ],
        'approval_required' => [
            'title' => 'Approval Required',
            'message' => 'An item requires your approval: :item.',
        ],
        'system_maintenance' => [
            'title' => 'System Maintenance',
            'message' => 'Scheduled maintenance at :time.',
        ],
    ];

    /**
     * @param  User|iterable  $recipients
     */
    public function send($recipients, string $template, array $params = [], array $channels = ['database']): void
    {
        $rendered = $this->render($template, $params);

        $users = $recipients instanceof User ? [$recipients] : $recipients;

        foreach ($users as $user) {
            if (in_array('database', $channels)) {
                Notification::create([
                    'user_id' => $user->id,
                    'channel' => 'database',
                    'type' => 'database',
                    'title' => $rendered['title'],
                    'message' => $rendered['message'],
                    'data' => $params,
                ]);
            }

            if (in_array('mail', $channels) && $user->email) {
                Mail::to($user->email)->queue(new SystemNotification(
                    $rendered['title'],
                    $rendered['message'],
                    $params
                ));
            }
        }
    }

    public function render(string $template, array $params): array
    {
        $base = $this->templates[$template] ?? ['title' => $template, 'message' => ''];

        return [
            'title' => $this->interpolate($base['title'], $params),
            'message' => $this->interpolate($base['message'], $params),
        ];
    }

    public function templates(): array
    {
        return $this->templates;
    }

    public function unreadForUser(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->get();
    }

    public function recentForUser(User $user, int $limit = 20): Collection
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markAllRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    protected function interpolate(string $string, array $params): string
    {
        foreach ($params as $key => $value) {
            $string = str_replace(':'.$key, $value, $string);
        }

        return $string;
    }
}
