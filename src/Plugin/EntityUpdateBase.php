<?php

namespace Drupal\gpb_commands\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\gpb_commands\Services\EntityUpdateInfoInterface;
use function array_diff_key;
use function array_fill_keys;


/**
 * Base class for Entity update plugins.
 */
abstract class EntityUpdateBase extends PluginBase implements EntityUpdateInterface {

  /** @var EntityTypeManagerInterface */
  private $entityTypeManager;

  /** @var EntityUpdateInfoInterface */
  private $updateService;

  /** @var EntityTypeInterface */
  private $entityType;

  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityUpdateInfoInterface $updateService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->updateService = $updateService;
  }

  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  public function getDependency() {
    return isset($this->pluginDefinition['dependency']) ? $this->pluginDefinition['dependency'] : [];
  }

  public function isDependencyComplete(array $dependency) {
    $dependency = array_fill_keys($dependency, TRUE);
    $plugin_dependency = array_fill_keys($this->getDependency(), $dependency);
    $diff = array_diff_key($plugin_dependency, $dependency);
    return count($diff) === 0;
  }

  public function getEntityType() {
    if (empty($this->entityType)) {
      try {
        $this->entityType = $this->entityTypeManager->getDefinition($this->getDerivativeId());
      } catch (PluginNotFoundException $e) {
      }
    }
    return $this->entityType;
  }

  protected function getUpdateService() {
    return $this->updateService;
  }

}
