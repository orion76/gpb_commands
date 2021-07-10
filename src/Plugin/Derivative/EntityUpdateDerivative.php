<?php

namespace Drupal\gpb_commands\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_filter;

class EntityUpdateDerivative extends DeriverBase implements ContainerDeriverInterface {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity_type.manager'));
  }

  public function getDerivativeDefinitions($base_plugin_definition) {

    foreach ($this->getEntityContentTypes() as $entity_type) {
      /** @var $entity_type EntityTypeInterface */
      $entity_type_id = $entity_type->id();
      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['entity_type_id'] = $entity_type_id;
    }
    return $this->derivatives;
  }
  
  protected function getEntityContentTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(),
      function (EntityTypeInterface $entityTYpe) {
        return $entityTYpe->getGroup() === 'content';
      });
  }

}
