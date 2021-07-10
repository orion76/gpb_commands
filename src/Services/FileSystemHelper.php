<?php


namespace Drupal\gpb_commands\Services;


use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\DrupalFinder;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function sprintf;

class FileSystemHelper implements FileSystemInterface {

  const FIELD_MAPPING_FILE = 'field_mapping.json';

  const SETTINGS_FILE = 'settings.yml';

  const TYPE_ROOT = 'root';

  const TYPE_DATA = 'data';

  /**
   * @var DrupalFinder;
   */
  protected $drupalFinder;

  protected $settings;

  protected $root_directory;

  protected $data_directory;

  private FileSystem $fs;

  private DrupalStyle $io;

  public function __construct(DrupalFinder $drupalFinder, $settings) {
    $this->drupalFinder = $drupalFinder;
    $this->settings = $settings;
    $this->fs = new FileSystem();
  }

  public function setIo(DrupalStyle $io) {
    $this->io = $io;
  }


  public function getIo() {
    return $this->io;
  }


  private function makeDirectory($path) {
    $status = TRUE;
    try {
      $this->fs->mkdir($path);
    } catch (IOExceptionInterface $e) {
      $status = FALSE;
      $this->io->error(
        sprintf(
          'Directory not created: %s',
          $e->getPath()
        )
      );
    }
    return $status;
  }

  public function getRootDirectory() {
    if (empty($this->root_directory)) {
      $drupal_root = $this->drupalFinder->getComposerRoot();
      $directory = $drupal_root . "/" . $this->settings['directories'][self::TYPE_ROOT];
      $this->makeDirectory($directory);
      $this->root_directory = $directory;
    }
    return $this->root_directory;
  }

  public function getDataDirectory() {
    if (empty($this->data_directory)) {
      $directory = $this->getRootDirectory() . "/" . $this->settings['directories'][self::TYPE_DATA];
      $this->makeDirectory($directory);
      $this->data_directory = $directory;
    }
    return $this->data_directory;
  }

  public function clearDataDirectory() {
    foreach (glob($this->getDataDirectory() . '/*') as $item) {
      $this->fs->remove($item);
    }
  }

  public function getFileNames() {
    $names = [];
    foreach (glob($this->getDataDirectory() . '/*') as $item) {
      $names[] = basename($item);
    }
    return $names;
  }

  public function getContent($file_name) {
    if ($content = file_get_contents($this->getFullPath(self::TYPE_DATA, $file_name))) {
      return $content;
    }
    $this->io->error('Failed to get file content' . $file_name);
    return FALSE;
  }

  public function getSettings() {
    if ($content = file_get_contents($this->getFullPath(self::TYPE_ROOT, self::SETTINGS_FILE))) {
      $settings = Yaml::decode($content);
      return $settings;
    }
    $this->io->error('Failed to get file content' . self::SETTINGS_FILE);
    return FALSE;
  }

  public function getFieldMapping() {

    if ($content = file_get_contents($this->getFullPath(self::TYPE_ROOT, self::FIELD_MAPPING_FILE))) {
      return $content;
    }
    $this->io->error('Failed to get file content' . self::FIELD_MAPPING_FILE);
    return FALSE;
  }

  public function getFullPath($type, $file_name) {
    $directory = NULL;

    switch ($type) {
      case self::TYPE_ROOT:
        $directory = $this->getRootDirectory();
        break;
      case self::TYPE_DATA:
        $directory = $this->getDataDirectory();
        break;
    }
    return "{$directory}/{$file_name}";
  }

  public function saveData($file_name, $data) {

    if (file_put_contents($this->getFullPath(self::TYPE_DATA, $file_name), $data) === FALSE) {
      $this->io->error('Failed to write file ' . $file_name);
      return FALSE;
    }
  }

  public function saveFieldMapping($data) {
    $file_name = self::FIELD_MAPPING_FILE;
    if (file_put_contents($this->getFullPath(self::TYPE_ROOT, $file_name), $data) === FALSE) {
      $this->io->error('Failed to write file ' . $file_name);
      return FALSE;
    }
  }

}
