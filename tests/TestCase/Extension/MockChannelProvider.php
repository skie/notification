<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase\Extension;

use Cake\Notification\Extension\ChannelProviderInterface;
use Cake\Notification\Registry\ChannelRegistry;

/**
 * Mock Channel Provider for testing
 */
class MockChannelProvider implements ChannelProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provides(): array
    {
        return ['mock'];
    }

    /**
     * @inheritDoc
     */
    public function register(ChannelRegistry $registry): void
    {
        $registry->load('mock', [
            'className' => MockChannel::class,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }
}
