<?php

namespace Drupal\gpb_commands\Plugin\EntityUpdate;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\gpb_commands\Plugin\EntityUpdateBase;
use Drupal\gpb_commands\Plugin\EntityUpdateInterface;
use Drupal\gpb_commands\Services\EntityUpdateInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_diff_key;

/**
 * @EntityUpdate(
 *   id = "content_entity_update",
 *   label = "Content entity fields",
 *   deriver = "Drupal\gpb_commands\Plugin\Derivative\EntityUpdateDerivative",
 * )
 *
 * @package Drupal\gpb_commands\Plugin\EntityUpdate
 */
class ContentEntityUpdate extends EntityUpdateBase implements EntityUpdateInterface {

  private $fieldsChanged;

  private EntityDefinitionUpdateManagerInterface $definitionManager;

  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityUpdateInfoInterface $updateService,
                              EntityDefinitionUpdateManagerInterface $definitionManager) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $updateService);
    $this->definitionManager = $definitionManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('gpb_commands.entity_update'),
      $container->get('entity.definition_update_manager'),
    );
  }

  private function getFieldsChanged() {

    if (empty($this->fieldsChanged)) {
      $entity_type_id = $this->getEntityType()->id();
      $installed = $this->getUpdateService()->getFieldsInstalled($entity_type_id);
      $definitions = $this->getUpdateService()->getFieldsDefinitions($entity_type_id);

      $inserted = array_diff_key($definitions, $installed);
      $deleted = array_diff_key($installed, $definitions);

      $this->fieldsChanged[EntityUpdateInfoInterface::INSERT] = $inserted;
      $this->fieldsChanged[EntityUpdateInfoInterface::DELETE] = $deleted;
    }

    return $this->fieldsChanged;
  }


  public function getInfo() {
    $entity_type_id = $this->getEntityType()->id();

    $header = [
      'entity_type' => 'Entity type',
      'field_name' => 'Field name',
      'action' => 'Action',
    ];

    $changed = $this->getFieldsChanged();
    $actions = [EntityUpdateInfoInterface::INSERT, EntityUpdateInfoInterface::DELETE];

    $rows = [];
    foreach ($actions as $action) {
      foreach ($changed[$action] as $field_definition) {
        if ($field_definition instanceof BaseFieldDefinition) {
          $row = [
            'entity_type' => $entity_type_id,
            'field_name' => $field_definition->getName(),
            'action' => $action,
          ];
        }
        else {
          $row = [
            'entity_type' => 'TODO',
            'field_name' => 'TODO',
            'action' => 'TODO',
          ];
        }
        $rows[] = $row;
      }
    }


    return ['header' => $header, 'rows' => $rows];
  }

  public function update() {
    $entity_type_id = $this->getEntityType()->id();
    $changed = $this->getFieldsChanged();
    $to_insert = $changed[EntityUpdateInfoInterface::INSERT];
    $success = [];
    $error = [];
    foreach ($to_insert as $field_name => $field_definition) {
      $report = [
        'entity_type' => $entity_type_id,
        'field_name' => $field_name,
      ];

      if ($field_definition instanceof BaseFieldDefinition) {
        $this->definitionManager->installFieldStorageDefinition($field_name, $entity_type_id, $field_definition->getProvider(), $field_definition);
        $report['state'] = 'created';
        $success[] = $report;
      }
      else {
        $report['state'] = 'ERROR';
        $error[] = $report;
      }
    }
    $header = ['Entity type', 'Field name', 'State'];

    return ['header' => $header, 'success' => $success, 'error' => $error];
  }

}
