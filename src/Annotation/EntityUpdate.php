<?php

namespace Drupal\gpb_commands\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Entity update item annotation object.
 *
 * @see \Drupal\gpb_commands\Plugin\EntityUpdateManager
 * @see plugin_api
 *
 * @Annotation
 */
class EntityUpdate extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
