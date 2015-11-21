<?php

use Mikulas\TestWorkers\Tests\Model;

/**
 * @covers XModel::a
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$model = new Model();
$model->b();
