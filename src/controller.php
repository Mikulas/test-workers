<?php

require __DIR__ . '/../vendor/autoload.php';

$parent = getmypid();
function debug($message) {
	return;
	global $parent;
	echo getmypid() . (getmypid() === $parent ? " \e[31mparent\e[0m: " : ' child: ') . "$message\n";
}

ini_set('zend.assertions', 1); // generate and execute code
ini_set('assert.exception', 1); // throw exceptions

$filesToRun = $argv;
array_shift($filesToRun);

$control = new ProcessControl(getmypid(), 3);
$mutex = new Mutex(sys_get_temp_dir());

if (PHP_SAPI !== 'phpdbg') {
	echo "Expected phpdbg SAPI, run as\n";
	echo "  phpdbg -qrr " . implode(' ', array_map('escapeshellarg', $argv)) . "\n";
	die;
}

if (version_compare(PHP_VERSION, '7.0.0RC7', '<')) {
	echo "PHP " . PHP_VERSION . " is not supported, use at PHP >=7.0.0RC7.\n";
	die;
}

while ($file = array_shift($filesToRun)) {
	if ($control->fork() === ProcessControl::CHILD) {
		// child
		debug("process '$file");
		ob_start();
		require_once $file;
		$output = ob_get_clean();
		$mutex->synchronizedStdOut(function() use ($file, $output) {
			echo "$file:\n";
			echo "$output\n";
		});
		debug("exit");
		die;

	} else {
		debug("loop");
		continue;
	}
}

// only parent will get here
$control->waitForChildren();

$counter = $control->getCounter();
echo $counter[ProcessControl::CODE_FAIL] . " failed tests\n";
echo $counter[ProcessControl::CODE_SKIP] . " skipped tests\n";
echo $counter[ProcessControl::CODE_SUCCESS] . " tests passed\n";

debug("exit");

if ($counter[ProcessControl::CODE_FAIL] !== 0) {
	exit(1);
}
exit(0);
