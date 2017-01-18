<?php

/**
 * @file
 * Contains Drupal\cors\EventSubscriber\CorsResponseEventSubscriber
 */

namespace Drupal\cors\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response Event Subscriber for adding CORS headers.
 */
class CorsResponseEventSubscriber implements EventSubscriberInterface {

  /**
   * The CORS config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new CORS response event subscriber.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher) {
    $this->config = $config_factory->get('cors.settings');
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Adds CORS headers to the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The GET response event.
   */
  public function addCorsHeaders(FilterResponseEvent $event) {

    $domains = $this->config->get('domains');
    $request = $event->getRequest();
    $path_info = $request->getPathInfo();
    $current_path = $this->aliasManager->getPathByAlias($path_info);
    $request_headers = $request->headers->all();
    $headers = array(
      'all' => array(
        'Access-Control-Allow-Origin' => array(),
        'Access-Control-Allow-Credentials' => array(),
      ),
      'OPTIONS' => array(
        'Access-Control-Allow-Methods' => array(),
        'Access-Control-Allow-Headers' => array(),
      ),
    );
    foreach ($domains as $path => $settings) {
      $settings = explode("|", $settings);
      $page_match = $this->pathMatcher->matchPath($current_path, $path);
      if ($current_path != $path_info) {
        $page_match = $page_match || $this->pathMatcher->matchPath($path_info, $path);
      }
      if ($page_match) {
        if (!empty($settings[0])) {
          $origins = explode(',', trim($settings[0]));
          foreach ($origins as $origin) {
            if ($origin === '<mirror>') {
              if (!empty($request_headers['Origin'])) {
                $headers['all']['Access-Control-Allow-Origin'][] = $request_headers['Origin'];
              }
            }
            else {
              $headers['all']['Access-Control-Allow-Origin'][] = $origin;
            }
          }

        }
        if (!empty($settings[1])) {
          $headers['OPTIONS']['Access-Control-Allow-Methods'] = explode(',', trim($settings[1]));
        }
        if (!empty($settings[2])) {
          $headers['OPTIONS']['Access-Control-Allow-Headers'] = explode(',', trim($settings[2]));
        }
        if (!empty($settings[3])) {
          $headers['all']['Access-Control-Allow-Credentials'] = explode(',', trim($settings[3]));
        }
      }
    }

    $response = $event->getResponse();

    foreach ($headers as $method => $allowed) {
      $current_method = $request->getMethod();
      if ($method === 'all' || $method === $current_method) {
        foreach ($allowed as $header => $values) {
          if (!empty($values)) {
            $response->headers->set($header, $values);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('addCorsHeaders');
    return $events;
  }

}
