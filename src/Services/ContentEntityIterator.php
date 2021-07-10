<?php


namespace Drupal\gpb_commands\Services;


use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\jsonapi_defaults\Controller\EntityResource;
use Iterator;

class ContentEntityIterator implements Iterator {

  private $position;

  private $ids;

  private $conditions;

  private EntityStorageInterface $storage;

  private EntityResource $resource ;
  //jsonapi.entity_resource
  public function __construct(EntityResource $resource,EntityStorageInterface $storage, $conditions) {
    $this->storage = $storage;
    $this->conditions = $conditions;
  }

  public function current() {
    $id = $this->ids[$this->position];
    return $this->storage->load($id);
  }

  public function next() {
    $this->position++;
  }

  public function key() {
    return $this->position;
  }

  public function valid() {
    return isset($this->ids[$this->position]);
  }

  public function rewind() {
    $query = $this->storage->getQuery();
    foreach ($this->conditions as $field_name => $value) {
      $query->condition($field_name, $value);
    }

    $this->ids = array_values($query->execute());
    $this->position = 0;
  }

}
