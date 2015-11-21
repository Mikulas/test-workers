<?php

class ProcessControl
{

	/** @var int */
	const PARENT = -2;

	/** @var int */
	const CHILD = 0;


	/** @var int */
	private $allowedPID;

	/** @var int[] */
	private $children;


	public function __construct($parentPID)
	{
		$this->allowedPID = $parentPID;
	}


	/**
	 * @return ProcessControl::PARENT|self::CHILD
	 */
	public function fork()
	{
		assert(getmypid() === $this->allowedPID, 'Fork only allowed from original parent process');

		$childPid = pcntl_fork();
		assert($childPid !== -1, 'Fork failed');

		if ($childPid === self::CHILD) {
			return self::CHILD;

		} else {
			$this->children[] = $childPid;
			return self::PARENT;
		}
	}

}
