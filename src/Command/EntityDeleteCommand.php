<?php

namespace Drupal\gpb_commands\Command;

use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function is_file;


/**
 * Class GenerateViewsCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="gpb_commands",
 *     extensionType="module"
 * )
 */
class EntityDeleteCommand extends Command {


  /** @var \Drupal\Core\Logger\LoggerChannelInterface */
  protected $logger;

  private EntityTypeManagerInterface $entityTypeManager;


  /**
   * Constructs a new GenerateViewsCommand object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('gpb:entity:delete')
      ->setAliases(['gpb:ed'])
      ->addArgument('module', InputOption::VALUE_REQUIRED, 'Module-provider')
      ->setDescription($this->trans('commands.gpb.entity.delete.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $module = $input->getArgument('module');
    if (empty($module)) {
      $output->writeln('Module parameter is missing.');
      return 1;
    }
    $types = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entityType) use ($module) {
      return $entityType->getProvider() === $module && $entityType instanceof ContentEntityType;
    });

    $this->getIo()->title('Entity types for delete:');
    $this->getIo()->block(array_keys($types));
    if (FALSE === $this->getIo()->confirm('Delete entity types?', TRUE)) {
      return 0;
    }
    foreach ($types as $entityType) {
      $storage = $this->entityTypeManager->getStorage($entityType->id());
      $entities = $storage->loadMultiple();
      $storage->delete($entities);
    }
  }


}
