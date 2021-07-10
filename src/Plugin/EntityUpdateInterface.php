<?php

namespace Drupal\gpb_commands\Plugin;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for Entity update plugins.
 */
interface EntityUpdateInterface extends PluginInspectionInterface,ContainerFactoryPluginInterface, DerivativeInspectionInterface {


  public function getLabel();

  public function getDependency();

  public function getEntityType();
  public function getInfo();

  public function update();
}
