<?php

namespace Drupal\gpb_commands\Plugin;


use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Provides the Entity update plugin manager.
 */
interface EntityUpdateManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  public function getInstances($entity_types = []);
  public function getUpgradableEntityTypeIds();
}
