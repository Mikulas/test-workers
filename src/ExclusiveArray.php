<?php

use Ardent\Collection\AvlTree;

class ProcessControl
{

	/** @var int */
	const PARENT = -2;

	/** @var int */
	const CHILD = 0;


	/** @var int */
	private $allowedPID;

	/** @var int[] pids */
	private $children;

	/** @var int */
	private $childLimit;


	public function __construct($parentPID, $childLimit = 10)
	{
		$this->allowedPID = $parentPID;
		$this->childLimit = $childLimit;
		$this->children = new AvlTree();
	}


	/**
	 * @return ProcessControl::PARENT|self::CHILD
	 */
	public function fork()
	{
		assert(getmypid() === $this->allowedPID, 'Fork only allowed from original parent process');

		while (count($this->children) >= $this->childLimit) {
			echo "waiting for child to exit\n";
			$exitedChild = pcntl_wait($status, WUNTRACED);
			assert($exitedChild !== -1, 'No child remaining, but $children not cleared');
			$this->collectStatus();
		}

		$childPid = pcntl_fork();
		assert($childPid !== -1, 'Fork failed');

		if ($childPid === self::CHILD) {
			return self::CHILD;

		} else {
			$this->children->add($childPid);
			return self::PARENT;
		}
	}


	protected function collectStatus()
	{
//		assert(getmypid() === $this->allowedPID, 'Collect status only allowed from original parent process');
//
//		foreach ($this->children as $childPID) {
//			pcntl_wait($childPID, WNOHANG)
//		}
	}


	public function collect()
	{
		$failsafe = 100;
		while ($this->children->count() !== 0) {
			echo "COLLECT waiting for child to exit\n";
			$exitedChild = pcntl_wait($status, WUNTRACED);
			assert($exitedChild !== -1, 'No child remaining, but $children not cleared');

			var_dumP("remove $exitedChild");
			$this->children->remove($exitedChild);

			assert(--$failsafe < 0, 'Failed to collect child processes');
		}
	}

}
