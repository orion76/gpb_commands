<?php

namespace Drupal\gpb_commands\Services;

interface EntityUpdateInfoInterface {

  const INSERT = 'insert';

  const DELETE = 'delete';

  public function updateFields();

  public function getBundles($entity_type_id);

  public function getFieldsDefinitions($entity_type_id);

  public function getFieldsInstalled($entity_type_id);

}
