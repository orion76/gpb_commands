<?php

namespace Drupal\gpb_commands;

use Drupal\Core\Entity\EntityTypeInterface;

class EntityTypeUpdateInfo implements EntityTypeUpdateInfoInterface {

  private EntityTypeInterface $entityType;

  private $fieldsInstalled;

  private $fieldsDefinitions;

  private $bundleInfo;

  public function __construct(EntityTypeInterface $entityType, $fieldsInstalled, $fieldsDefinitions, $bundleInfo) {
    $this->entityType = $entityType;
    $this->fieldsInstalled = $fieldsInstalled;
    $this->fieldsDefinitions = $fieldsDefinitions;
    $this->bundleInfo = $bundleInfo;
  }

  public function getInserted() {

  }

  public function getDeleted() {

  }


}
