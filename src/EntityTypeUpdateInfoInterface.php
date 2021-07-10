<?php

namespace Drupal\gpb_commands;

interface EntityTypeUpdateInfoInterface {

  public function getInserted();

  public function getDeleted();

}
