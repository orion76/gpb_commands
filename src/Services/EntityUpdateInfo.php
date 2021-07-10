<?php


namespace Drupal\gpb_commands\Services;


use Drupal\bundle_handler\Plugin\BundleHandlerManagerInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use function array_diff_key;
use function array_filter;
use function array_keys;

class EntityUpdateInfo implements EntityUpdateInfoInterface {


  protected EntityTypeManagerInterface $entityTypeManager;

  protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager;

  protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository;

  protected EntityTypeBundleInfoInterface $bundleInfoService;

  protected EntityFieldManagerInterface $entityFieldManager;

  protected BundleHandlerManagerInterface $bundleHandlerManager;

  /**
   * Constructs a new UpdateEntitiesCommand object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
                              EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
                              EntityTypeBundleInfoInterface $bundleInfoService,
                              EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
    $this->entityLastInstalledSchemaRepository = $entityLastInstalledSchemaRepository;
    $this->bundleInfoService = $bundleInfoService;
    $this->entityFieldManager = $entityFieldManager;

  }

  public function updateFields() {
    $updatedFields = $this->getUpdatedFields();
    $n = 0;
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

  public function getBundles($entity_type_id) {
    return $this->bundleInfoService->getBundleInfo($entity_type_id);
  }

  public function getFieldsInstalled($entity_type_id) {
    //    $result = [];
    $fieldsInstalled_1 = $this->entityLastInstalledSchemaRepository
      ->getLastInstalledFieldStorageDefinitions($entity_type_id);

    $fieldsInstalled = $this->entityLastInstalledSchemaRepository
      ->getLastInstalledFieldStorageDefinitions($entity_type_id);
    return $fieldsInstalled;
    //    $common_fields = $this->filterCommonFields($fieldsInstalled);
    //    $bundles = $this->bundleInfoService->getBundleInfo($entity_type_id);
    //    foreach (array_keys($bundles) as $bundle) {
    //      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    //      $fields = $this->filterBundleFields($fields, $bundle);
    //      $result[$bundle] = [
    //        'common' => $common_fields,
    //        'bundle' => array_intersect_key($fieldsInstalled, $fields),
    //      ];
    //    }
    //    return $result;
  }

  public function getFieldsDefinitions($entity_type_id) {
//    $fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
//    $diff=array_diff_key($fieldsStorage,$fields);
    return $fields;

  }

}
