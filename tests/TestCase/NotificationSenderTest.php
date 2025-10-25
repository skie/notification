<?php
declare(strict_types=1);

namespace Cake\Notification\Test\TestCase;

use Cake\Notification\NotificationManager;
use Cake\Notification\NotificationSender;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * NotificationSender Test Case
 *
 * Tests the notification sending logic and event dispatching
 */
class NotificationSenderTest extends TestCase
{
    /**
     * Fixtures to load
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Cake/Notification.Notifications',
    ];

    /**
     * Set up test case
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (NotificationManager::getRegistry()->has('database')) {
            NotificationManager::drop('database');
        }

        NotificationManager::setConfig('database', [
            'className' => 'Cake/Notification.Database',
        ]);
    }

    /**
     * Tear down test case
     *
     * @return void
     */
    public function tearDown(): void
    {
        NotificationManager::drop('database');
        NotificationManager::getRegistry()->reset();

        parent::tearDown();
    }

    /**
     * Test that sendNow sends notification through channel
     *
     * @return void
     */
    public function testSendNowSendsNotification(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $sender->sendNow($entity, $notification);

        $notificationsTable = TableRegistry::getTableLocator()->get('Cake/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class])
            ->first();

        $this->assertNotNull($saved);
        $this->assertEquals('Users', $saved->model);
        $this->assertEquals('1', $saved->foreign_key);
        $this->assertEquals(['message' => 'Test message'], $saved->data);
    }

    /**
     * Test that sendNow dispatches sent event
     *
     * @return void
     */
    public function testSendNowDispatchesSentEvent(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $eventFired = false;
        $sender->getEventManager()->on('Model.Notification.sent', function () use (&$eventFired) {
            $eventFired = true;
        });

        $sender->sendNow($entity, $notification);

        $this->assertTrue($eventFired);
    }

    /**
     * Test that formatNotifiables handles single entity
     *
     * @return void
     */
    public function testFormatNotifiablesHandlesSingleEntity(): void
    {
        $sender = new NotificationSender();
        $entity = new Entity();

        $reflection = new ReflectionClass($sender);
        $method = $reflection->getMethod('formatNotifiables');
        $method->setAccessible(true);

        $result = $method->invoke($sender, $entity);

        $this->assertIsIterable($result);
        $resultArray = is_array($result) ? $result : iterator_to_array($result);
        $this->assertEquals([$entity], $resultArray);
    }
}
