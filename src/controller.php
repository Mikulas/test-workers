<?php

require __DIR__ . '/../vendor/autoload.php';

define('PRINT_DEBUG', FALSE);
define('MAX_PROCESSES', 50);
define('COVERAGE_ENABLED', TRUE);

$parent = getmypid();
function debug($message) {
	if (!PRINT_DEBUG) {
		return;
	}

	global $parent;
	echo getmypid() . (getmypid() === $parent ? " \e[31mparent\e[0m: " : ' child: ') . "$message\n";
}

ini_set('zend.assertions', 1); // generate and execute code
ini_set('assert.exception', 1); // throw exceptions

// TODO REFACTOR
$filesToRun = $argv;
array_shift($filesToRun);

$control = new ProcessControl(getmypid(), MAX_PROCESSES);
$mutex = new Mutex(sys_get_temp_dir());
$collector = new CoverageCollector($mutex);

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
		debug("process '$file");
		ob_start();

		if (COVERAGE_ENABLED) {
			$collector->collect(function() use ($file) {
				require_once $file;
			}, $file);

		} else {
			require_once $file;
		}

		$output = ob_get_clean();
		$mutex->synchronized(Mutex::STD_OUT, function() use ($file, $output) {
			if ($output) {
				echo "\e[1;34m$file:\e[0m\n";
				echo "$output\n\n";
			}
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

echo "Generating code coverage report\n";

$phpUnitCoverage = PhpUnitCoverageFactory::create($collector->getCoverages());
$collector->destroy();

$writer = new PHP_CodeCoverage_Report_Clover;
$writer->process($phpUnitCoverage, dirname(__DIR__) . '/phpunit.xml.dist');

//$writer = new PHP_CodeCoverage_Report_HTML;
//$writer->process($phpUnitCoverage, '/tmp/code-coverage-report');

if ($counter[ProcessControl::CODE_FAIL] !== 0) {
	exit(1);
}
exit(0);
