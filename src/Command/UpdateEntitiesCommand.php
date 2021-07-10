<?php

namespace Drupal\gpb_commands\Command;

use Drupal\Console\Core\Command\Command;
use Drupal\gpb_commands\Plugin\EntityUpdate\ContentEntityUpdateInterface;
use Drupal\gpb_commands\Plugin\EntityUpdateInterface;
use Drupal\gpb_commands\Plugin\EntityUpdateManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_diff_key;
use function array_fill_keys;

/**
 * Class UpdateEntitiesCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="gpb_commands",
 *     extensionType="module"
 * )
 */
class UpdateEntitiesCommand extends Command {

  protected EntityUpdateManager $entityUpdateManager;

  /**
   * Constructs a new UpdateEntitiesCommand object.
   */
  public function __construct(EntityUpdateManager $entityUpdateManager) {
    $this->entityUpdateManager = $entityUpdateManager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('gpb:update:entities')
      ->setDescription($this->trans('commands.gpb.update.entities.description'))
      ->setAliases(['gue']);
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
  }

  private function createTitle(EntityUpdateInterface $plugin) {
    $plugin_label = $plugin->getLabel();
    $entity_type_label = $plugin->getEntityType()->getLabel();
    return "{$plugin_label}: {$entity_type_label}";
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $entity_type_ids = $this->entityUpdateManager->getUpgradableEntityTypeIds();
    $plugins = $this->entityUpdateManager->getInstances($entity_type_ids);

    $has_updates = FALSE;
    foreach ($plugins as $plugin) {
      /** @var $plugin EntityUpdateInterface */
      $info = $plugin->getInfo();
      if (empty($info['rows'])) {
        continue;
      }
      $has_updates = TRUE;

      $this->getIo()->title($this->createTitle($plugin));
      $this->getIo()->table($info['header'], $info['rows']);
    }

    if (FALSE === $has_updates) {
      $this->getIo()->text('No updates');
      return;
    }

    if (FALSE === $this->getIo()->confirm('Update entities?')) {
      return;
    }

    foreach ($plugins as $plugin) {
      /** @var $plugin EntityUpdateInterface */
      $info = $plugin->update();
      $this->getIo()->title($this->createTitle($plugin));
      foreach (['success', 'error'] as $state_name) {
        if (empty($info[$state_name])) {
          continue;
        }
        $this->getIo()->commentBlock("State:{$state_name}");
        $this->getIo()->table($info['header'], $info[$state_name]);
      }


    }
  }

  public function isNext(array $plugin_dependency, array $complete) {
    $complete = array_fill_keys($complete, TRUE);
    $plugin_dependency = array_fill_keys($plugin_dependency, TRUE);
    $diff = array_diff_key($plugin_dependency, $complete);
    return count($diff) === 0;
  }

  public function isDependencyComplete(array $plugin_dependency, array $complete) {
    $complete = array_fill_keys($complete, TRUE);
    $plugin_dependency = array_fill_keys($plugin_dependency, TRUE);
    $diff = array_diff_key($plugin_dependency, $complete);
    return count($diff) === 0;
  }

}
