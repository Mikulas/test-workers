<?php

function fork(callable $parent, callable $child, $repeat = 1)
{
	$pid = pcntl_fork();
	if ($pid == -1) {
		die('could not fork');

	} elseif ($pid) {
		$parent();
		if ($repeat > 0) {
			fork($parent, $child, $repeat - 1);
		}
		pcntl_wait($status); // protect against zombie children

	} else {
		$child();
	}
}

$filesToRun = $argv;
array_shift($filesToRun);

$spawnChildren = 10;
$parentKernel = function() {
	echo getmypid() . " parent\n";
};
$childKernel = function() {
	echo getmypid() . " child\n";
	//sleep(1);
	echo getmypid() . "  child done\n";
};

fork($parentKernel, $childKernel, $spawnChildren);
echo "exit\n";
