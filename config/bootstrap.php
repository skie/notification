<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Notification\Channel\BroadcastChannel;
use Cake\Notification\Channel\DatabaseChannel;
use Cake\Notification\Channel\MailChannel;
use Cake\Notification\NotificationManager;

NotificationManager::setConfig('database', [
    'className' => DatabaseChannel::class,
]);

NotificationManager::setConfig('broadcast', [
    'className' => BroadcastChannel::class,
]);

NotificationManager::setConfig('mail', [
    'className' => MailChannel::class,
    'profile' => 'default',
]);

$config = Configure::read('Notification');

if (!$config && file_exists(__DIR__ . '/notification.php')) {
    $config = require __DIR__ . '/notification.php';
    Configure::write($config);
}

if (isset($config['Notification']['channels'])) {
    foreach ($config['Notification']['channels'] as $name => $channelConfig) {
        NotificationManager::setConfig($name, $channelConfig);
    }
}
