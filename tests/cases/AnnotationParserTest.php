<?php

namespace Mikulas\TestWorkers\Tests;

use Mikulas\TestWorkers\AnnotationParser;
require __DIR__ . '/../boostrap.php';

/**
 * @covers AnnotationParser
 */

$file = __DIR__ . '/../fixtures/usings.php';

$parser = new AnnotationParser();

$expectations = [
	'Root\Something' => 'Something',
	'Alpha\Beta' => 'Alias',
	'Alpha\Beta\Foo' => 'Alias\Foo',
	'Alpha\Beta\Gama' => 'Gama',
	'Gama' => '\Gama',
	'Alpha\Beta2' => 'Beta2',
	'Alpha\Beta3' => 'Beta3',
];

foreach ($expectations as $fqn => $name) {
	$real = $parser->toFqn($file, $name);
	assert($fqn === $real, "'$name' FQN should be '$fqn' !== '$real'");
}
