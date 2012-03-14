<?php

namespace triagens;

class AvocadoAutoloader {
  public static function load($className) {
    $namespace = __NAMESPACE__ . '\\';
    if (substr($className, 0, strlen($namespace)) !== $namespace) {
      return;
    }

    $libDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;

    require_once $libDir . substr($className, strlen($namespace)) . ".php";
  }
}

spl_autoload_register(__NAMESPACE__ . '\AvocadoAutoloader::load');
