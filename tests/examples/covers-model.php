<?php

use Mikulas\TestWorkers\Tests as Renamed;

/**
 * @covers Renamed\Model
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$model = new Renamed\Model();
$model->b();
