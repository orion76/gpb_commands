<?php

namespace Drupal\gpb_commands\Exception;

use Exception;
use Throwable;

class CyclicDependenceException extends Exception {

  public function __construct($items, $code = 0, Throwable $previous = NULL) {
    $message = "Cyclic dependence of the plugin @EntityUpdate";
    parent::__construct($message, $code, $previous);
  }
}
