<?php

namespace Drupal\gpb_commands\Services;

use Drupal\Console\Core\Style\DrupalStyle;

interface FileSystemInterface {

  public function getRootDirectory();

  public function getDataDirectory();

  public function clearDataDirectory();

  public function getFileNames();

  public function getContent($file_name);

  public function getFieldMapping();

  public function saveData($file_name, $data);

  public function saveFieldMapping($data);

  public function setIo(DrupalStyle $io);

  public function getIo();
  public function getSettings();
}
