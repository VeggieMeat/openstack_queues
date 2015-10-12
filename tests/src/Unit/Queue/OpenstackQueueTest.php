<?php

namespace Drupal\Tests\openstack_queues\Unit\Queue;

use Drupal\openstack_queues\Queue\OpenstackQueue;
use Drupal\Tests\openstack_queues\Unit\OpenstackQueueTestBase;
use Drupal\Tests\UnitTestCase;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use OpenCloud\Rackspace;
use OpenCloud\Tests\MockSubscriber;

/**
 * @coversDefaultClass \Drupal\openstack_queues\Queue\OpenstackQueue
 * @group openstack_queues
 */
class OpenstackQueueTest extends OpenstackQueueTestBase {

  protected function setUp() {
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub([
      'openstack_queues.settings' => [
        'default' => [
          'client_id' => '9bcc7ac3-3754-467e-96f1-b51c9168ed3c',
          'auth_url' => 'https://identity.api.rackspacecloud.com/v2.0/',
          'region' => 'DFW',
          'prefix' => '',
          'credentials' => [
            'username' => 'bender',
            'apiKey' => 'rodriguez',
          ],
        ],
        'foo' => [
          'client_id' => '3911f4f8-bc26-483c-909f-7848a100a58e',
          'auth_url' => 'https://identity.api.rackspacecloud.com/v2.0/',
          'region' => 'IAD',
          'prefix' => 'bar',
          'credentials' => [
            'username' => 'turanga',
            'apiKey' => 'leela',
          ],
        ],
      ],
    ]);
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->method('get')->with('config.factory')->will($this->returnValue($config_factory));
    \Drupal::setContainer($container);

    $config = \Drupal::config('openstack_queues.settings')->get('default');

    $this->client = new Rackspace($config['auth_url'], $config['credentials']);
    $this->client->addSubscriber(new MockSubscriber());
    $this->queue = new OpenstackQueue('foo', $this->client, $config);
  }

  public function testCreateItem() {
    $data = 'Do homework';
    $this->assertTrue($this->queue->createItem($data));
  }

  public function testClaimItem() {
    $this->addMockSubscriber($this->makeResponse('[
   {
      "body":{
         "event":"BackupStarted"
      },
      "age":239,
      "href":"/v1/queues/demoqueue/messages/51db6f78c508f17ddc924357?claim_id=51db7067821e727dc24df754",
      "ttl":300
   }
]', 201));
    $this->assertNotFalse($this->queue->claimItem());
  }

  public function testNumberOfItems() {
    $this->assertNotNull($this->queue->numberOfItems());
  }

  public function testReleaseItem() {
    $item = new \stdClass();
    $item->body = 'Do homework';
    $item->item_id = '123';
    $this->assertTrue($this->queue->releaseItem($item));
  }

}