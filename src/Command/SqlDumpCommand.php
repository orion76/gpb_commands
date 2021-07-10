<?php

namespace Drupal\gpb_commands\Command;

use DateTime;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ShellProcess;
use Drupal\Core\Database\Connection;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function array_flip;
use function basename;
use function file_exists;
use function sprintf;

/**
 * Class SqlDumpCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="gpb_commands",
 *     extensionType="module"
 * )
 */
class SqlDumpCommand extends Command {

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
      ->setName('gpb:sql:dump')
      ->setDescription($this->trans('commands.gpb.sql.dump.description'))
      ->setAliases(['gpb:dd'])
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
  protected function execute(InputInterface $input, OutputInterface $output) {
    $database = "default";
    $target = "default";
    $custom_symlink = $input->getArgument('file');

    $databaseConnection = $this->escapeConnection($this->resolveConnection($database, $target));

    $date = new DateTime();
    $file = sprintf(
      '%s/%s-%s.sql',
      $this->dumpDir,
      $databaseConnection['database'],
      $date->format('Y-m-d-H-i-s')
    );

    $command = sprintf(
      "mysqldump --user='%s' --password='%s' --host='%s' --port='%s' '%s' > '%s'",
      $databaseConnection['username'],
      $databaseConnection['password'],
      $databaseConnection['host'],
      $databaseConnection['port'],
      $databaseConnection['database'],
      $file
    );

    try {
      $process = Process::fromShellCommandline($command);
      $process->setTimeout(NULL);
      $process->setWorkingDirectory($this->backupDir);
      $process->run();

      if ($process->isSuccessful()) {
        $resultFile = $file;


        $report = [];
        $report[] = sprintf('%s %s', "", basename($resultFile));

        if ($custom_symlink) {
          $symlink_path = $this->getSymlinkPath($custom_symlink);
          $this->createSymlink($symlink_path, $resultFile);
          $report[] = sprintf('%s %s', "", basename($symlink_path));
        }

        $symlink_last = $this->getSymlinkPath(self::SYMLINK_LAST);
        $this->createSymlink($symlink_last, $resultFile);
        $report[] = sprintf('%s %s', "", basename($symlink_last));

        $this->getIo()->successLite('Export success', TRUE);
        $this->getIo()->listing($report);

      }

      if (!file_exists($file)) {
        $this->getIo()->error(sprintf('Dump file "%s" not created!', $file));
      }
      return 0;
    } catch (Exception $e) {
      return 1;
    }
  }

}
