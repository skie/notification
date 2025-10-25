<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase\Channel;

use Cake\Notification\Notification;
use Cake\ORM\Entity;

/**
 * Test entity with routeNotificationForMail method
 */
class TestRoutableEntity extends Entity
{
    /**
     * Route notification for mail channel
     *
     * @param \Cake\Notification\Notification $notification Notification instance
     * @return string
     */
    public function routeNotificationForMail(Notification $notification): string
    {
        return 'routed@example.com';
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource(): string
    {
        return 'Users';
    }
}
