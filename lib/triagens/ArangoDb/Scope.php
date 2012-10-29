<?php

/**
 * ArangoDB PHP client: scope
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Executes a function on scope entry and executes another function
 * (cleanup) when the scope is exited 
 *
 * @package ArangoDbPhpClient
 */
class Scope {
  /**
   * Initialization function (called on scope entry)
   * 
   * @var callable
   */
  private $_initFunc;
  
  /**
   * Exit function (called when scope is left)
   * 
   * @var callable
   */
  private $_exitFunc;
  
  /**
   * Value returned by call to init function
   * 
   * @var mixed
   */
  private $_value;
  
  /**
   * Current scope state
   * 
   * @var mixed
   */
  private $_state = self::STATE_NONE;

  /**
   * Scope has not been entered
   */
  const STATE_NONE    = 0;
  
  /**
   * Scope has been entered but not left
   */
  const STATE_ENTERED = 1;
  
  /**
   * Scope has been entered and left
   */
  const STATE_LEFT    = 2;

  /**
   * Initialise the scope
   *
   * @param callable $initFunc - function to be called when scope is entered
   * @param callable $exitFunc - function to be called when scope if left
   * @return void
   */
  public function __construct($initFunc, $exitFunc) {
    assert(is_callable($initFunc));
    assert(is_callable($exitFunc));

    $this->_initFunc = $initFunc;
    $this->_exitFunc = $exitFunc;

    $this->enter();
  }

  /**
   * Destroy the scope
   * 
   * This will call leave() to ensure the scope is definitely left
   *
   * @return void
   */
  public function __destruct() {
    $this->leave();
  }
  
  /**
   * Leave the scope
   * 
   * This will execute the exit func with the value returned by the
   * call to the init func.
   * leave() will check whether the scope has already been left to avoid
   * duplicate execution of the exit func.
   *
   * @return void
   */
  public function leave() {
    if ($this->_state < self::STATE_ENTERED) {
      return;
    }

    $this->_state = self::STATE_LEFT;

    // call exit function
    $func = $this->_exitFunc;
    $func($this->_value);
  }

  /**
   * Enter the scope
   * 
   * This will call the init func and store its result value
   *
   * @return void
   */
  private function enter() {
    // call init func
    $func = $this->_initFunc;
    $this->_value = $func();

    $this->_state = self::STATE_ENTERED;
  }

  /**
   * Clone function
   * 
   * Scopes should never be cloned so this is private
   *
   * @throws ClientException
   * @return void
   */
  private function __clone() {
    throw new ClientException('This should never happen');
  }
}
