<?php

namespace Mikulas\TestWorkers\Tests;

use Mikulas\TestWorkers\Mutex;

require __DIR__ . '/../boostrap.php';

/**
 * @covers Mutex
 */

$mutex = new Mutex(sys_get_temp_dir());

$tempFile = tempnam(sys_get_temp_dir(), __CLASS__);

$childPid = pcntl_fork();
assert($childPid !== -1, 'Fork failed');

$mutex->synchronized([], function() {});

// parent writes from T=0 to T=1 second
// child starts to write at T=0.5 to T=2
// parent starts to write again at T=1.5 to T=2.5

$T = microtime(TRUE);
if ($childPid) {
	// parent branch
	$mutex->synchronized(1, function() use ($tempFile, $T) {
		while (microtime(TRUE) - $T < 1.0) {
			file_put_contents($tempFile, "state 1\n", FILE_APPEND);
			usleep(1000);
		}
	});

	while (microtime(TRUE) - $T < 1.5) {
		usleep(100);
	}

	$mutex->synchronized(1, function() use ($tempFile, $T) {
		while (microtime(TRUE) - $T < 2.5) {
			file_put_contents($tempFile, "state 3\n", FILE_APPEND);
			usleep(1000);
		}
	});

	// wait for child to exit
	pcntl_wait($status, WUNTRACED);
	assert(pcntl_wifexited($status), 'Child process not exited');
	$childExitCode = pcntl_wexitstatus($status);

	// verify that Mutex created exclusive access
	$handle = fopen($tempFile, 'r');
	assert($handle);
	$state = 1;
	while (($line = fgets($handle)) !== FALSE) {
		if ($state === 1 && $line === 'state 2') {
			$state = 2;

		} elseif ($state === 2 && $line === 'state 3') {
			$state = 3;

		} else {
			assert($line !== "state $state");
		}
	}
	fclose($handle);
	unlink($tempFile);

	if ($childExitCode) {
		throw new \Exception(NULL, $childExitCode);
	}

} else {
	while (microtime(TRUE) - $T < 0.5) {
		usleep(100);
	}

	$mutex->synchronized(1, function() use ($tempFile, $T) {
		while (microtime(TRUE) - $T < 2.0) {
			file_put_contents($tempFile, "state 2\n", FILE_APPEND);
			usleep(1000);
		}
	});

	exit; // prevent coverage from this branch overriding parent branch
}
