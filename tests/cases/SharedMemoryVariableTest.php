<?php

ini_set('zend.assertions', 1); // generate and execute code
ini_set('assert.exception', 1); // throw exceptions

require __DIR__ . '/../../vendor/autoload.php';

$mutex = new Mutex(sys_get_temp_dir());
$shared = new SharedMemoryVariable($mutex, 100, []);

$childPid = pcntl_fork();
assert($childPid !== -1, 'Fork failed');

// Sleep child for 1 second while parent writes [1, 2, 3]
// Read in child while parent sleeps
// Write 'foo' in child while parent sleeps
// Exit child and read in parent

$syncDuration = 200000;

if ($childPid) {
	// parent

	echo "parent writing [1, 2, 3]\n";
	$delta = microtime(TRUE);
	$shared->save([1, 2, 3]);
	echo "parent sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));

	echo "parent sleeps\n";
	usleep($syncDuration);

	echo "parent sleeps\n";
	usleep($syncDuration);

	echo "parent verifies\n";
	$delta = microtime(TRUE);
	$data = $shared->get();
	assert($data === 'foo', 'Not able to read shared data');
	echo "parent sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));


	// waif for child to exit
	echo "parent waiting\n";
	pcntl_wait($status, WUNTRACED);
	assert(pcntl_wifexited($status), 'Child process not exited');
	$childExitCode = pcntl_wexitstatus($status);

	$shared->destroy();

	try {
		$shared->destroy();
	} catch (AssertionError $e) {}
	assert($e !== NULL, 'SharedVariable::destroy must throw after call to destroy()');

	try {
		$shared->get();
	} catch (AssertionError $e) {}
	assert($e !== NULL, 'SharedVariable::get must throw after call to destroy()');

	try {
		$shared->save([1]);
	} catch (AssertionError $e) {}
	assert($e !== NULL, 'SharedVariable::save must throw after call to destroy()');

	echo "parent exit\n";
	exit($childExitCode);

} else {
	// child
	echo "child sleeps\n";
	usleep($syncDuration);

	echo "child verifies\n";
	$delta = microtime(TRUE);
	$data = $shared->get();
	assert($data === [1, 2, 3], 'Not able to read shared data');
	echo "child sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));

	echo "child writes\n";
	$delta = microtime(TRUE);
	$shared->save('foo');
	echo "child sleeps\n";
	usleep($syncDuration - (microtime(TRUE) - $delta));

	echo "child exit\n";
}
