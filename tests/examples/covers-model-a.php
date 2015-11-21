<?php

use Mikulas\TestWorkers\Tests\Model;

/**
 * @covers Model::a
 */


require_once __DIR__ . '/../../vendor/autoload.php';

$model = new Model();
$model->a();
$model->b();
