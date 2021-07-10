<?php

namespace Drupal\gpb_commands;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Session\AccountEvents;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountSetEvent;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * A proxied implementation of AccountInterface.
 *
 * The reason why we need an account proxy is that we don't want to have global
 * state directly stored in the container.
 *
 * This proxy object avoids multiple invocations of the authentication manager
 * which can happen if the current user is accessed in constructors. It also
 * allows legacy code to change the current user where the user cannot be
 * directly injected into dependent code.
 */
class ConsoleAccountProxy extends AccountProxy implements AccountProxyInterface {

  use DependencySerializationTrait;

  /**
   * The instantiated account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Account id.
   *
   * @var int
   */
  protected $id = 1;

  /**
   * {@inheritdoc}
   */
  public function setAccount(AccountInterface $account) {
    // If the passed account is already proxied, use the actual account instead
    // to prevent loops.
    if ($account instanceof static) {
      $account = $account->getAccount();
    }
    $this->account = $account;
    $this->id = $account->id();
    $this->eventDispatcher->dispatch(new AccountSetEvent($account), AccountEvents::SET_USER);
  }
 
  /**
   * {@inheritdoc}
   */
  public function ___setInitialAccountId($account_id) {
    parent::setInitialAccountId(1);
  }

}
