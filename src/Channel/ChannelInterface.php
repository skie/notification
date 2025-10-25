<?php
declare(strict_types=1);

namespace Cake\Notification\Channel;

use Cake\Datasource\EntityInterface;
use Cake\Notification\AnonymousNotifiable;
use Cake\Notification\Notification;

/**
 * Channel Interface
 *
 * Defines the contract for all notification channel implementations.
 * Channels are responsible for delivering notifications through specific delivery mechanisms.
 */
interface ChannelInterface
{
    /**
     * Send the given notification
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Cake\Notification\Notification $notification The notification to send
     * @return mixed Channel-specific response (e.g., saved entity for database, response for webhook)
     */
    public function send(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): mixed;
}
