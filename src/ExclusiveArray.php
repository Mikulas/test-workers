<?php

use Ardent\Collection\AvlTree;

class ProcessControl
{

	/** @var int microseconds */
	const FAILSAFE_TIMEOUT = 90 * 1e6;

	/** @var int */
	const PARENT = -2;

	/** @var int */
	const CHILD = 0;


	/** @var int */
	private $allowedPID;

	/** @var int[] pids as keys */
	private $children;

	/** @var int */
	private $childLimit;


	public function __construct($parentPID, $childLimit = 10)
	{
		$this->allowedPID = $parentPID;
		$this->childLimit = $childLimit;
		$this->children = [];
	}


	/**
	 * @return ProcessControl::PARENT|self::CHILD
	 */
	public function fork()
	{
		assert(getmypid() === $this->allowedPID, 'Fork only allowed from original parent process');

		while (count($this->children) >= $this->childLimit) {
			debug("waiting for any child to report status\n");
			pcntl_wait($status, WUNTRACED);
			$this->collectStatus();
		}

		$childPid = pcntl_fork();
		assert($childPid !== -1, 'Fork failed');

		if ($childPid === self::CHILD) {
			return self::CHILD;

		} else {
			$this->children[$childPid] = TRUE;
			return self::PARENT;
		}
	}


	/**
	 * Removes $children PIDs that have exited
	 */
	protected function collectStatus()
	{
		assert(getmypid() === $this->allowedPID, 'Collect status only allowed from original parent process');

		foreach ($this->children as $childPID => $_) {
			$ret = pcntl_waitpid($childPID, $status, WNOHANG);
			assert($ret !== -1, "Collect status failed, count not get status of '$childPID'");

			if ($ret !== 0) {
				debug("$childPID is dead");
				unset($this->children[$childPID]);
			} else {
				debug("$childPID is still alive");
			}
		}
	}


	/**
	 * Blocks until all children have exited
	 */
	public function waitForChildren()
	{
		$step = 2e5; // 200 ms
		$failsafe = self::FAILSAFE_TIMEOUT / $step;

		while (count($this->children) !== 0) {
			$this->collectStatus();
			usleep($step);

			assert(--$failsafe > 0, 'Failed to collect child processes');
		}
	}

}
