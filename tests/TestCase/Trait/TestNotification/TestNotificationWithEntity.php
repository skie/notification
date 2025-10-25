<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Cake\Notification\AnonymousNotifiable;
use Cake\Notification\Notification;

/**
 * Test Notification With Entity Property
 */
class TestNotificationWithEntity extends Notification
{
    /**
     * @var mixed
     */
    protected mixed $post;

    /**
     * Constructor
     *
     * @param mixed $post Post entity or data
     */
    public function __construct(mixed $post)
    {
        $this->post = $post;
    }

    /**
     * Get channels
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable Notifiable
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get post
     *
     * @return mixed
     */
    public function getPost(): mixed
    {
        return $this->post;
    }
}
