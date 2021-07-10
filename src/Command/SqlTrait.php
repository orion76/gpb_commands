<?php


namespace Drupal\gpb_commands\Command;


use function dirname;
use function file_exists;
use function is_link;
use function symlink;
use function unlink;

trait SqlTrait {


  
  protected $rootDir;

  protected $backupDir;

  protected $dumpDir;

  protected function initDirs($appRoot){
    $this->rootDir = dirname($appRoot, 1);
    $this->backupDir = $this->rootDir . "/backup";
    $this->dumpDir = $this->backupDir . "/dump";
  }
  protected function createSymlink($symlink, $target) {
    if (file_exists($symlink) || is_link($symlink)) {
      unlink($symlink);
    }
    symlink($target, $symlink);
  }

  protected function getSymlinkPath($name) {
    return $this->backupDir . "/{$name}";
  }
}
