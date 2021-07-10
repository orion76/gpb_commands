<?php

namespace Drupal\gpb_commands\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Extension\Manager;
use const FILE_APPEND;

/**
 * Class GenerateViewsGenerator.
 *
 * @package Drupal\Console\Generator
 */
class GenerateViewsGenerator extends Generator implements GeneratorInterface {
  /**
   * @var Manager
   */
  protected $extensionManager;

  public function __construct(
    Manager $extensionManager
  ) {
    $this->extensionManager = $extensionManager;
  }
  
  private function getModuleRoot(){
    $module_handler = \Drupal::service('module_handler');
    return $module_handler->getModule('gpb_commands')->getPath();
  }
  
  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {

    $module = $parameters['module'];
    $entity_type = $parameters['entity-type'];
    $moduleDir = $this->extensionManager->getModule($module)->getPath();


    $this->renderer->setSkeletonDirs([$this->getModuleRoot().'/templates']);
    
    $this->renderFile(
      'views/views.view.admin_entity.yml.twig',
      $moduleDir . '/config/install/views.view.admin__' . $entity_type . '.yml',
      $parameters
    );
  }

}
