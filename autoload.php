<?php

namespace triagens\Avocado;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'triagens' . DIRECTORY_SEPARATOR . 'Avocado' . DIRECTORY_SEPARATOR . 'Autoloader.php';

Autoloader::init();

spl_autoload_register(__NAMESPACE__ . '\Autoloader::load');
