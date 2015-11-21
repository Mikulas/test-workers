<?php

require __DIR__ . '/../vendor/autoload.php';

$parent = getmypid();
function debug($message) {
	global $parent;
	echo getmypid() . (getmypid() === $parent ? " \e[31mparent\e[0m: " : ' child: ') . "$message\n";
}


$filesToRun = $argv;
array_shift($filesToRun);

$control = new ProcessControl(getmypid(), 3);

while ($file = array_shift($filesToRun)) {
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
pcntl_wait($status); // protect against zombie children
debug("exit");
