<?php

namespace Mikulas\TestWorkers;


class ProcessManager
{

	/** @var int microseconds */
	const FAILSAFE_TIMEOUT = 90 * 1e6;


	/** @var int */
	const PARENT = -2;

	/** @var int */
	const CHILD = 0;


	/** @var int */
	const CODE_SUCCESS = 0;

	/** @var int */
	const CODE_SKIP = 126;

	/** @var int tests were not even run properly */
	const CODE_ERROR = 127;

	/** @var int tests run successfully but failed */
	const CODE_FAIL = -1; // any other unused code


	/** @var int */
	private $allowedPID;

	/** @var int[] pids as keys */
	private $children;

	/** @var int */
	private $childLimit;

	/** @var int[] */
	private $counter = [
		self::CODE_SUCCESS => 0,
		self::CODE_SKIP => 0,
		self::CODE_FAIL => 0,
		self::CODE_ERROR => 0,
	];

	/** @var callable */
	private $debugCallback;


	public function __construct(int $parentPID, int $childLimit = 10)
	{
		$this->allowedPID = $parentPID;
		$this->childLimit = $childLimit;
		$this->children = [];
	}


	/**
	 * @return ProcessManager::PARENT|self::CHILD
	 */
	public function fork() : int
	{
		assert(getmypid() === $this->allowedPID, 'Fork only allowed from original parent process');

		while (count($this->children) >= $this->childLimit) {
			$this->debug("waiting for any child to report status\n");
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

			if ($ret === 0) {
				$this->debug("$childPID is still alive");
				continue;
			}

			$this->debug("$childPID is dead");
			assert(pcntl_wifexited($status));
			switch ($status = pcntl_wexitstatus($status)) {
				case self::CODE_SUCCESS:
				case self::CODE_SKIP:
				case self::CODE_ERROR:
					$this->counter[$status]++;
					break;
				default:
					$this->counter[self::CODE_FAIL]++;
					break;
			}
			unset($this->children[$childPID]);
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


	/**
	 * @return int[]
	 */
	public function getCounter() : array
	{
		return $this->counter;
	}


	/**
	 * @param callable $callback
	 */
	public function setDebugCallback(callable $callback)
	{
		$this->debugCallback = $callback;
	}


	/**
	 * @param string $message
	 */
	protected function debug($message)
	{
		if ($cb = $this->debugCallback) {
			$cb($message);
		}
	}

}
