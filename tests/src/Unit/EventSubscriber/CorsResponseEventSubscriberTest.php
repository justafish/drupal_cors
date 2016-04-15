<?php

/**
 * @file
 * Contains Drupal\Tests\cors\Unit\EventSubscriber\CorsResponseEventSubscriberTest.
 */

namespace Drupal\Tests\cors\Unit\EventSubscriber;

use Drupal\Tests\UnitTestCase;
use Drupal\cors\EventSubscriber\CorsResponseEventSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 *
 */
class CorsResponseEventSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'CORS Response Event Subscriber',
      'description' => 'Tests the CORS response event subscriber',
      'group' => 'CORS',
    );
  }

  protected function setupSubscriber() {
    $config_factory = $this->getConfigFactoryStub(array("cors.settings" => array("domains" => array('*' => 'http://example.com|POST,GET|Content-type,Authorization|true '))));
    $alias_manager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
    $path_matcher = $this->getMock('Drupal\Core\Path\PathMatcherInterface');
    $path_matcher->expects($this->any())
      ->method('matchPath')
      ->withAnyParameters()
      ->will($this->returnValue(TRUE));

    // Create the response event subscriber.
    $subscriber = new CorsResponseEventSubscriber($config_factory, $alias_manager, $path_matcher);
    return $subscriber;
  }

  /**
   * Tests adding CORS headers to the response.
   */
  public function testAddCorsHeaders() {
    $subscriber = $this->setupSubscriber();

    // Create the response event.
    $http_kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $request = new Request();
    $response = new Response();
    $event = new FilterResponseEvent($http_kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

    // Call the event handler.
    $subscriber->addCorsHeaders($event);

    $this->assertEquals(['http://example.com'], $response->headers->get('access-control-allow-origin', NULL, FALSE), "The access-control-allow-origin header was set");
    $this->assertEquals(['true'], $response->headers->get('Access-Control-Allow-Credentials', NULL, FALSE));
    $this->assertEquals([], $response->headers->get('Access-Control-Allow-Methods', NULL, FALSE));
    $this->assertEquals([], $response->headers->get('Access-Control-Allow-Headers', NULL, FALSE));
  }

  public function testOptionsRequest() {
    $subscriber = $this->setupSubscriber();

    // Create the response event.
    $http_kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $request = Request::create('/example', 'OPTIONS');
    $response = new Response();
    $event = new FilterResponseEvent($http_kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

    // Call the event handler.
    $subscriber->addCorsHeaders($event);

    $this->assertEquals(['http://example.com'], $response->headers->get('access-control-allow-origin', NULL, FALSE), "The access-control-allow-origin header was set");
    $this->assertEquals(['true'], $response->headers->get('Access-Control-Allow-Credentials', NULL, FALSE));
    $this->assertEquals(['POST', 'GET'], $response->headers->get('Access-Control-Allow-Methods', NULL, FALSE));
    $this->assertEquals(['Content-type', 'Authorization'], $response->headers->get('Access-Control-Allow-Headers', NULL, FALSE));
  }

}
