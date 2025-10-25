<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase;

use Cake\Datasource\EntityInterface;
use Cake\Notification\AnonymousNotifiable;
use Cake\Notification\Notification;

/**
 * Test Database Notification
 *
 * Simple notification for testing database functionality
 */
class TestDatabaseNotification extends Notification
{
    /**
     * Get channels
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get database data
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string, mixed>
     */
    public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'message' => 'Test message',
        ];
    }
}
