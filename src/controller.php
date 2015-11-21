<?php

require __DIR__ . '/../vendor/autoload.php';

$filesToRun = $argv;
array_shift($filesToRun);

$control = new ProcessControl(getmypid(), 2);

while ($file = array_shift($filesToRun)) {
	if ($control->fork() === ProcessControl::CHILD) {
		// child
		echo "child: process '$file'\n";
		sleep(1);
		echo "child exit\n";
		die;

	} else {
		echo "parent: loop\n";
		continue;
	}
}

// only parent will get here
pcntl_wait($status); // protect against zombie children
echo "parent exit\n";
