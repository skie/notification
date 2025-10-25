<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase\Extension;

use Cake\Notification\Channel\ChannelInterface;
use Cake\Notification\Notification;

/**
 * Mock Channel for testing
 */
class MockChannel implements ChannelInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function send(object $notifiable, Notification $notification): mixed
    {
        return null;
    }
}
