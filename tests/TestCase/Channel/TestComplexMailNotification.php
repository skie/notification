<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase\Channel;

use Cake\Datasource\EntityInterface;
use Cake\Notification\AnonymousNotifiable;
use Cake\Notification\Message\MailMessage;
use Cake\Notification\Notification;

/**
 * Test complex mail notification with all features
 */
class TestComplexMailNotification extends Notification
{
    /**
     * Get notification delivery channels
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get mail representation with all features
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return \Cake\Notification\Message\MailMessage
     */
    public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
    {
        return MailMessage::create()
            ->subject('Complex Notification')
            ->from('sender@example.com', 'Sender Name')
            ->replyTo('reply@example.com', 'Reply Name')
            ->cc('cc@example.com', 'CC Name')
            ->bcc('bcc@example.com')
            ->success()
            ->greeting('Hello User!')
            ->line('First intro line')
            ->lineIf(true, 'Conditional line')
            ->action('Take Action', 'https://example.com/action')
            ->line('First outro line')
            ->salutation('Best Regards, Team');
    }

    /**
     * Get array representation
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return array<string, mixed>
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['message' => 'Complex'];
    }
}
