<?php
declare(strict_types=1);

namespace Cake\Notification;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\I18n\I18n;
use Cake\Notification\Job\SendQueuedNotificationJob;
use Cake\ORM\TableRegistry;
use Cake\Queue\QueueManager;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Throwable;

/**
 * Notification Sender
 *
 * Handles the logic of dispatching notifications to entities through channels.
 * Manages locale switching, event dispatching, and queue/immediate sending logic.
 */
class NotificationSender
{
    use EventDispatcherTrait;

    /**
     * Locale for notifications
     *
     * @var string|null
     */
    protected ?string $locale;

    /**
     * Constructor
     *
     * @param string|null $locale Optional locale for notifications
     */
    public function __construct(?string $locale = null)
    {
        $this->locale = $locale;
    }

    /**
     * Send notification to notifiables
     *
     * If notification implements ShouldQueueInterface, it will be queued.
     * Otherwise, it will be sent immediately.
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Cake\Notification\Notification $notification The notification to send
     * @return void
     */
    public function send(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueueInterface) {
            $this->queueNotification($notifiables, $notification);

            return;
        }

        $this->sendNow($notifiables, $notification);
    }

    /**
     * Send notification immediately
     *
     * Sends the notification immediately through all specified channels,
     * bypassing the queue even if notification implements ShouldQueueInterface.
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Cake\Notification\Notification $notification The notification to send
     * @param array<string>|null $channels Optional channels to use
     * @return void
     */
    public function sendNow(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification, ?array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            $viaChannels = $channels ?: $notification->via($notifiable);

            if (empty($viaChannels)) {
                continue;
            }

            $this->withLocale(
                $this->preferredLocale($notifiable, $notification),
                function () use ($viaChannels, $notifiable, $original): void {
                    $notificationId = Text::uuid();

                    foreach ((array)$viaChannels as $channel) {
                        $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
                    }
                },
            );
        }
    }

    /**
     * Send notification to a single notifiable via a specific channel
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param string $id Unique notification ID
     * @param \Cake\Notification\Notification $notification The notification instance
     * @param string $channel The channel name
     * @return void
     */
    protected function sendToNotifiable(EntityInterface|AnonymousNotifiable $notifiable, string $id, Notification $notification, string $channel): void
    {
        if (!$notification->getId()) {
            $notification->setId($id);
        }

        if (!$this->shouldSendNotification($notifiable, $notification, $channel)) {
            return;
        }

        try {
            $channelInstance = NotificationManager::channel($channel);
            $response = $channelInstance->send($notifiable, $notification);

            $this->dispatchEvent('Model.Notification.sent', [
                'notifiable' => $notifiable,
                'notification' => $notification,
                'channel' => $channel,
                'response' => $response,
            ],);
        } catch (Throwable $exception) {
            $this->dispatchEvent('Model.Notification.failed', [
                'notifiable' => $notifiable,
                'notification' => $notification,
                'channel' => $channel,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    /**
     * Determine if notification should be sent
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param \Cake\Notification\Notification $notification The notification instance
     * @param string $channel The channel name
     * @return bool True if notification should be sent
     */
    protected function shouldSendNotification(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification, string $channel): bool
    {
        $event = $this->dispatchEvent('Model.Notification.sending', [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'channel' => $channel,
        ]);

        if ($event->isStopped()) {
            return false;
        }

        return $notification->shouldSend($notifiable, $channel);
    }

    /**
     * Format notifiables into a consistent iterable format
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @return iterable<\Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable> Normalized iterable of notifiables
     */
    protected function formatNotifiables(EntityInterface|AnonymousNotifiable|iterable $notifiables): iterable
    {
        if (!is_iterable($notifiables)) {
            return [$notifiables];
        }

        return $notifiables;
    }

    /**
     * Get the preferred locale for the notification
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param \Cake\Notification\Notification $notification The notification instance
     * @return string|null The preferred locale
     */
    protected function preferredLocale(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): ?string
    {
        if ($notification->getLocale()) {
            return $notification->getLocale();
        }

        if ($this->locale) {
            return $this->locale;
        }

        if (method_exists($notifiable, 'preferredLocale')) {
            return $notifiable->preferredLocale();
        }

        return null;
    }

    /**
     * Queue notification instances for later processing
     *
     * @param iterable<object> $notifiables The entities to notify
     * @param \Cake\Notification\Notification $notification The notification to queue
     * @return void
     */
    protected function queueNotification(iterable $notifiables, Notification $notification): void
    {
        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (!$notifiable instanceof EntityInterface) {
                continue;
            }

            $notificationId = Text::uuid();
            $viaChannels = $original->via($notifiable);

            if (empty($viaChannels)) {
                continue;
            }

            foreach ((array)$viaChannels as $channel) {
                $queuedNotification = clone $original;

                if (!$queuedNotification->getId()) {
                    $queuedNotification->setId($notificationId);
                }

                if ($this->locale !== null) {
                    $queuedNotification->locale($this->locale);
                }

                $table = TableRegistry::getTableLocator()->get($notifiable->getSource());
                $primaryKeyField = $table->getPrimaryKey();
                if (is_array($primaryKeyField)) {
                    $primaryKeyField = $primaryKeyField[0];
                }

                $jobData = [
                    'notifiableModel' => $notifiable->getSource(),
                    'notifiableForeignKey' => (string)$notifiable->get($primaryKeyField),
                    'notification' => serialize($queuedNotification),
                    'channels' => [$channel],
                ];

                $options = [];
                if ($queuedNotification->getQueue() !== null) {
                    $options['queue'] = $queuedNotification->getQueue();
                }
                if ($queuedNotification->getConnection()) {
                    $options['config'] = $queuedNotification->getConnection();
                }
                if ($queuedNotification->getDelay()) {
                    $options['delay'] = $queuedNotification->getDelay();
                }

                QueueManager::push(
                    SendQueuedNotificationJob::class,
                    $jobData,
                    $options,
                );
            }
        }
    }

    /**
     * Execute callback with specific locale
     *
     * @param string|null $locale The locale to use
     * @param callable $callback The callback to execute
     * @return void
     */
    protected function withLocale(?string $locale, callable $callback): void
    {
        if ($locale === null) {
            $callback();

            return;
        }

        $original = I18n::getLocale();
        I18n::setLocale($locale);

        try {
            $callback();
        } finally {
            I18n::setLocale($original);
        }
    }

    /**
     * Get the routing information for a given notification channel
     *
     * Checks for a channel-specific routing method on the notifiable entity.
     * For example, for the 'telegram' channel, it looks for a method named
     * 'routeNotificationForTelegram()' on the entity.
     *
     * This allows entities to define custom routing logic for different channels:
     * - telegram → routeNotificationForTelegram()
     * - slack → routeNotificationForSlack()
     * - twilio → routeNotificationForTwilio()
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param string $channel The channel name
     * @return mixed The routing information, or null if not available
     */
    public function getRoutingInfo(EntityInterface|AnonymousNotifiable $notifiable, string $channel): mixed
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return $notifiable->routeNotificationFor($channel, null);
        }

        $method = 'routeNotificationFor' . Inflector::camelize($channel);

        if (method_exists($notifiable, $method)) {
            return $notifiable->{$method}();
        }

        return null;
    }
}
