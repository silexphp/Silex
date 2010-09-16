<?php

require_once __DIR__.'/src/autoload.php';

use Silex\Compiler;

$compiler = new Compiler();
$compiler->compile();
