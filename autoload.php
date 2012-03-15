<?php

namespace triagens;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'AvocadoAutoloader.php';

AvocadoAutoloader::init();

spl_autoload_register(__NAMESPACE__ . '\AvocadoAutoloader::load');
