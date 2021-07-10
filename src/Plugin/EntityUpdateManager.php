<?php

namespace Drupal\gpb_commands\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\gpb_commands\Exception\CyclicDependenceException;
use Traversable;
use function array_column;
use function array_combine;
use function array_fill_keys;
use function array_filter;
use function array_intersect_key;
use function array_map;

/**
 * Provides the Entity update plugin manager.
 */
class EntityUpdateManager extends DefaultPluginManager implements EntityUpdateManagerInterface {


  private $instances;

  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new EntityUpdateManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler,
                              EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct('Plugin/EntityUpdate', $namespaces, $module_handler, 'Drupal\gpb_commands\Plugin\EntityUpdateInterface', 'Drupal\gpb_commands\Annotation\EntityUpdate');

    $this->entityTypeManager = $entityTypeManager;

    $this->alterInfo('gpb_commands_gpb_entity_update_info');
    $this->setCacheBackend($cache_backend, 'gpb_commands_gpb_entity_update_plugins');
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\gpb_commands\Exception\CyclicDependenceException
   */
  public function getInstances($entity_types = []) {

    if (empty($this->instances)) {
      $instances = array_map(function ($definition) {
        return $this->createInstance($this->createPluginId($definition));
      }, $this->getDefinitions());

      $items = array_map(function (EntityUpdateInterface $plugin) {
        return ['plugin' => $plugin, 'dependency' => $plugin->getDependency()];
      }, $instances);

      /**
       * тут обязательно нужен array_map, чтобы сохранились ключи массива,
       *  возвращенного $this->sortDependency($items)
       */
      $this->instances = array_map(function ($item) {
        return $item['plugin'];
      }, $this->sortDependency($items));

    }

    if (empty($entity_types)) {
      $return = $this->instances;
    }
    else {
      $entity_type_ids = array_fill_keys($entity_types, TRUE);
      $return = array_filter($this->instances, function (EntityUpdateInterface $plugin) use ($entity_type_ids) {
        return isset($entity_type_ids[$plugin->getEntityType()->id()]);
      });
    }

    return $return;
  }

  public function getUpgradableEntityTypeIds() {
    $entity_types = array_filter($this->entityTypeManager->getDefinitions(),
      function (EntityTypeInterface $entity_type) {
        return $entity_type->get('upgradable');
      });

    return array_map(function (EntityTypeInterface $entity_type) {
      return $entity_type->id();
    }, $entity_types);
  }

  private function sortDependency($items, $result = []) {
    $next_result = array_filter($items, function ($item) {
      return empty($item['dependency']);
    });
    if (empty($next_result)) {
      throw new CyclicDependenceException($items);
    }
    $result += $next_result;
    $items = array_diff_key($items, $result);

    if (empty($items)) {
      return $result;
    }

    foreach (array_keys($items) as $key) {
      $inter = array_intersect_key($items[$key]['dependency'], $result);
      foreach (array_keys($inter) as $key1) {
        NestedArray::unsetValue($items, [$key, 'dependency', $key1]);
      }
    }
    return $this->sortDependency($items, $result);
  }

  private function createPluginId($definition) {
    return "{$definition['id']}:{$definition['entity_type_id']}";
  }

  private function ___testSort() {
    $items = [
      'item_1' => ['plugin' => 'item_1', 'dependency' => []],
      'item_2' => ['plugin' => 'item_2', 'dependency' => ['item_5']],
      'item_3' => ['plugin' => 'item_3', 'dependency' => ['item_4']],
      'item_4' => ['plugin' => 'item_4', 'dependency' => ['item_1']],
      'item_5' => ['plugin' => 'item_5', 'dependency' => ['item_3']],
    ];

    $items = array_map(function ($item) {
      $item['dependency'] = array_combine($item['dependency'], $item['dependency']);
      $item['_dependency'] = $item['dependency'];
      return $item;
    }, $items);

    $result = $this->sortDependency($items);
    foreach ($result as $item) {
      $n = 0;
    }
  }

}
