<?php

namespace Drupal\gpb_commands\Command;

use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ShellProcess;
use Drupal\Core\Database\Connection;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function array_diff;
use function scandir;
use function sprintf;

/**
 * Class SqlRestoreCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="gpb_commands",
 *     extensionType="module"
 * )
 */
class SqlRestoreCommand extends Command {

  use ConnectTrait;

  use SqlTrait;

  const SYMLINK_LAST = '_last';

  /**
   * @var ShellProcess
   */
  protected $shellProcess;

  /**
   * @var Connection
   */
  protected $database;

  /**
   * DumpCommand constructor.
   *
   * @param $appRoot
   * @param ShellProcess $shellProcess
   * @param Connection $database
   */
  public function __construct(
    $appRoot,
    ShellProcess $shellProcess,
    Connection $database
  ) {

    $this->initDirs($appRoot);

    $this->shellProcess = $shellProcess;
    $this->database = $database;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('gpb:sql:restore')
      ->setDescription($this->trans('commands.gpb.sql.restore.description'))
      ->setAliases(['gpb:dr'])
      ->addArgument(
        'file',
        InputArgument::OPTIONAL,
        $this->trans('commands.database.dump.arguments.database'),
        NULL
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    $symlink = $input->getArgument('file');
    if (empty($symlink)) {
      $files = array_values(array_diff(scandir($this->backupDir), ['.', '..', 'dump']));

      $symlink = $this->getIo()->choice(
        $this->trans('commands.config.delete.arguments.type'),
        $files
      );
      $input->setArgument('file', $symlink);

    }


  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $database = 'default';
    $target = 'default';
    $symlink = $input->getArgument('file');

    if (empty($symlink)) {
      $symlink = self::SYMLINK_LAST;
    }

    $file = $this->getSymlinkPath($symlink);

    $databaseConnection = $this->escapeConnection($this->resolveConnection($database, $target));


    $catCommand = 'cat %s | ';


    $commands = [];
    if ($databaseConnection['driver'] == 'mysql') {
      // Drop database first.
      $commands[] = sprintf(
        "MYSQL_PWD='%s' mysql --user='%s' --host='%s' --port='%s' -e'DROP DATABASE IF EXISTS %s'",
        $databaseConnection['password'],
        $databaseConnection['username'],
        $databaseConnection['host'],
        $databaseConnection['port'],
        $databaseConnection['database']
      );

      // Recreate database.
      $commands[] = sprintf(
        "MYSQL_PWD='%s' mysql --user='%s' --host='%s' --port='%s' -e'CREATE DATABASE %s'",
        $databaseConnection['password'],
        $databaseConnection['username'],
        $databaseConnection['host'],
        $databaseConnection['port'],
        $databaseConnection['database']
      );

      // Import dump.
      $commands[] = sprintf(
        "cat %s | MYSQL_PWD='%s' mysql --user='%s' --host='%s' --port='%s' %s",
        $file,
        $databaseConnection['password'],
        $databaseConnection['username'],
        $databaseConnection['host'],
        $databaseConnection['port'],
        $databaseConnection['database']
      );
    }

    foreach ($commands as $command) {

      $process = new Process($command);
      $process->setTimeout(NULL);
      $process->setWorkingDirectory($this->backupDir);
      $process->setTty($input->isInteractive());
      $process->run();

      if (!$process->isSuccessful()) {
        throw new RuntimeException($process->getErrorOutput());
      }
    }

    $this->getIo()->success(
      sprintf(
        '%s %s',
        $this->trans('commands.database.restore.messages.success'),
        $file
      )
    );

    return 0;
  }

}
