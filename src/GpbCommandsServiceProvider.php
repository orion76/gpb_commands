<?php


namespace Drupal\gpb_commands;


use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;


class GpbCommandsServiceProvider extends ServiceProviderBase {

  
  public function __alter(ContainerBuilder $container) {
    if (php_sapi_name() === 'cli' && $container->hasDefinition('current_user')) {
      $definition = $container->getDefinition('current_user');
      $definition->setClass('Drupal\gpb_commands\ConsoleAccountProxy');
    }
  }

}
