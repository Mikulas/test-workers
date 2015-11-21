<?php

require __DIR__ . '/../vendor/autoload.php';

$parent = getmypid();
function debug($message) {
	global $parent;
	echo getmypid() . (getmypid() === $parent ? " \e[31mparent\e[0m: " : ' child: ') . "$message\n";
}

ini_set('zend.assertions', 1); // generate and execute code
ini_set('assert.exception', 1); // throw exceptions

$filesToRun = $argv;
array_shift($filesToRun);

$control = new ProcessControl(getmypid(), 3);

while ($file = array_shift($filesToRun)) {
	var_dumP("FILE", $file);
	if ($control->fork() === ProcessControl::CHILD) {
		// child
		debug("process '$file");
		sleep(1);
		debug("exit");
		die;

	} else {
		debug("loop");
		continue;
	}
}

// only parent will get here
$control->collect();
debug("exit");
