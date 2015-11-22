<?php

namespace Mikulas\TestWorkers\Tests;

use Mikulas\TestWorkers\Mutex;
use Mikulas\TestWorkers\SharedFileVariable;

require __DIR__ . '/../boostrap.php';

/**
 * @covers SharedFileVariable
 */

$mutex = new Mutex(sys_get_temp_dir());
$shared = new SharedFileVariable($mutex, ['initial' => 'value']);

assert(['initial' => 'value'] === $shared->get(), 'Initial value is saved');

$childPid = pcntl_fork();
assert($childPid !== -1, 'Fork failed');

// Sleep child for 1 second while parent writes [1, 2, 3]
// Read in child while parent sleeps
// Write 'foo' in child while parent sleeps
// Exit child and read in parent

$syncDuration = 200000;

if ($childPid) {
	// parent

	//echo "parent writing [1, 2, 3]\n";
	$delta = microtime(TRUE);
	$shared->set([1, 2, 3]);
	//echo "parent sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));

	//echo "parent sleeps\n";
	usleep($syncDuration);

	//echo "parent sleeps\n";
	usleep($syncDuration);

	//echo "parent verifies\n";
	$delta = microtime(TRUE);
	$data = $shared->get();
	assert($data === 'foo', 'Not able to read shared data');
	//echo "parent sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));


	// waif for child to exit
	//echo "parent waiting\n";
	pcntl_wait($status, WUNTRACED);
	assert(pcntl_wifexited($status), 'Child process not exited');
	$childExitCode = pcntl_wexitstatus($status);

	$shared->destroy();

	$e = NULL;
	try {
		$shared->destroy();
	} catch (\AssertionError $e) {}
	assert($e !== NULL, 'SharedVariable::destroy must throw after call to destroy()');

	$e = NULL;
	try {
		$shared->get();
	} catch (\AssertionError $e) {}
	assert($e !== NULL, 'SharedVariable::get must throw after call to destroy()');

	$e = NULL;
	try {
		$shared->set([1]);
	} catch (\AssertionError $e) {}
	assert($e !== NULL, 'SharedVariable::save must throw after call to destroy()');

	//echo "parent exit\n";
	if ($childExitCode) {
		throw new \Exception(NULL, $childExitCode);
	}

} else {
	// child
	//echo "child sleeps\n";
	usleep($syncDuration);

	//echo "child verifies\n";
	$delta = microtime(TRUE);
	$data = $shared->get();
	assert($data === [1, 2, 3], 'Not able to read shared data');
	//echo "child sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));

	//echo "child writes\n";
	$delta = microtime(TRUE);
	$shared->set('foo');
	//echo "child sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));

	exit; // prevent coverage from this branch overriding parent branch

	//echo "child exit\n";
}
