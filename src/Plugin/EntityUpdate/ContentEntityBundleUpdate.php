<?php

namespace Drupal\gpb_commands\Plugin\EntityUpdate;

use Drupal\bundle_handler\Plugin\BundleHandlerManagerInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\gpb_commands\Plugin\EntityUpdateBase;
use Drupal\gpb_commands\Plugin\EntityUpdateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_keys;

/**
 * @TODOEntityUpdate(
 *   id = "content_entity_bundle_update",
 *   label = "Content entity bundle fields",
 *   dependency = {
 *     "content_entity_update"
 *   }
 *   deriver = "Drupal\gpb_commands\Plugin\Derivative\EntityUpdateDerivative",
 * )
 *
 * @package Drupal\gpb_commands\Plugin\EntityUpdate
 */
class ContentEntityBundleUpdate extends EntityUpdateBase implements EntityUpdateInterface {

  const INSERTED = 'inserted';

  const DELETED = 'deleted';


  /** @var  EntityTypeInterface */
  private $entityType;

  private $fieldsInstalled;

  private $fieldsDefinition;

  private $bundleFieldsDefinitions;

  private $fieldsChanged;

  protected EntityTypeManagerInterface $entityTypeManager;

  protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager;

  protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository;

  protected EntityTypeBundleInfoInterface $bundleInfoService;

  protected EntityFieldManagerInterface $entityFieldManager;

  protected BundleHandlerManagerInterface $bundleHandlerManager;

  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
                              EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
                              EntityTypeBundleInfoInterface $bundleInfoService,
                              EntityFieldManagerInterface $entityFieldManager,
                              BundleHandlerManagerInterface $bundleHandlerManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
    $this->entityLastInstalledSchemaRepository = $entityLastInstalledSchemaRepository;
    $this->bundleInfoService = $bundleInfoService;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleHandlerManager = $bundleHandlerManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.definition_update_manager'),
      $container->get('entity.last_installed_schema.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.bundle_handler')
    );
  }

  private function getEntityType() {
    if (empty($this->entityType)) {
      $this->entityType = $this->entityTypeManager->getDefinition($this->getDerivativeId());
    }
    return $this->entityType;
  }

  private function filterBundleFields($fields, $bundle) {
    return array_filter($fields, function ($field) use ($bundle) {
      if ($field instanceof BaseFieldDefinition) {
        return $field->getTargetBundle() === $bundle;
      }
      return FALSE;
    });
  }

  private function filterCommonFields($fields) {
    return array_filter($fields, function ($field) {
      if ($field instanceof BaseFieldDefinition) {
        return empty($field->getTargetBundle());
      }
      return TRUE;
    });
  }

  private function getFieldsInstalled() {
    if (empty($this->fieldsInstalled)) {
      $fieldsInstalled = $this->entityLastInstalledSchemaRepository
        ->getLastInstalledFieldStorageDefinitions($this->getEntityType()->id());

      $common_fields = $this->filterCommonFields($fieldsInstalled);
      $bundles = $this->bundleInfoService->getBundleInfo($this->getEntityType()->id());
      foreach (array_keys($bundles) as $bundle) {
        $fields = $this->entityFieldManager->getFieldDefinitions($this->getEntityType()->id(), $bundle);
        $fields = $this->filterBundleFields($fields, $bundle);
        $this->fieldsInstalled[$bundle] = [
          'common' => $common_fields,
          'bundle' => array_intersect_key($fieldsInstalled, $fields),
        ];
      }
    }
    return $this->fieldsInstalled;
  }


  private function getFieldsDefinitions() {
    if (empty($this->fieldsDefinition)) {
      $entityType = $this->getEntityType();
      foreach (array_keys($this->bundleInfoService->getBundleInfo($entityType->id())) as $bundle) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entityType->id(), $bundle);
        $common_fields = $this->filterCommonFields($fields);
        $this->fieldsDefinition[$bundle] = [
          'common' => $common_fields,
          'bundle' => array_diff_key($fields, $common_fields),
        ];
        $this->fieldsDefinition[$bundle]['bundle'] += $this->getBundleFieldsDefinitions($bundle);
      }
    }
    return $this->fieldsDefinition;
  }

  private function getBundleFieldsDefinitions($bundle) {
    if (empty($this->bundleFieldsDefinitions)) {
      $entityType = $this->getEntityType();

      foreach ($this->bundleHandlerManager->getEntityTypeDefinitions($entityType->id()) as $definition) {
        /** @var $instance \Drupal\bundle_handler\Plugin\BundleHandlerInterface */
        $instance = $this->bundleHandlerManager->createInstance($definition['id']);
        $this->bundleFieldsDefinitions[$instance->getPluginId()] = $instance->buildFieldDefinitions();
      }

    }
    return isset($this->bundleFieldsDefinitions[$bundle]) ? $this->bundleFieldsDefinitions[$bundle] : [];
  }

  private function getFieldsChanged() {
    if (empty($this->fieldsChanged)) {
      $installed = $this->getFieldsInstalled();
      $definitions = $this->getFieldsDefinitions();

      $bundles = $this->bundleInfoService->getBundleInfo($this->getEntityType()->id());
      foreach (array_keys($bundles) as $bundle) {
        $bundle_installed = $installed[$bundle];
        $bundle_definitions = $definitions[$bundle];

        $inserted = ['common' => [], 'bundle' => []];
        $inserted['common'] = array_diff_key($bundle_definitions['common'], $bundle_installed['common']);
        $inserted['bundle'] = array_diff_key($bundle_definitions['bundle'], $bundle_installed['bundle']);

        $deleted = ['common' => [], 'bundle' => []];
        $deleted['common'] = array_diff_key($bundle_installed['common'], $bundle_definitions['common']);
        $deleted['bundle'] = array_diff_key($bundle_installed['bundle'], $bundle_definitions['bundle']);


        $this->fieldsChanged[self::INSERTED][$bundle] = $inserted;
        $this->fieldsChanged[self::DELETED][$bundle] = $deleted;
      }


    }
    return $this->fieldsChanged;
  }


  private function getFieldsInserted() {
    $changed = $this->getFieldsChanged();
    return isset($changed[self::INSERTED]) ? $changed[self::INSERTED] : [];
  }

  private function getFieldsDeleted() {
    $changed = $this->getFieldsChanged();
    return isset($changed[self::DELETED]) ? $changed[self::DELETED] : [];
  }

  public function getInfo() {
    //    $bundleFields=$this->getBundleFieldsDefinitions();
$entity_type_id=$this->getEntityType()->id();
    $inserted = $this->getFieldsInserted();
    $deleted = $this->getFieldsDeleted();
    $info=[];
    

    
    
    $n = 0;
    $info = [
      'inserted' => [],
      'deleted' => [],
    ];


    return $info;
  }

  public function update() {

  }

}
