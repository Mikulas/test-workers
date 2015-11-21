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

	/** @var AvlTree */
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

		while ($this->children->count() >= $this->childLimit) {
			echo "waiting for child to exit\n";
			$exitedChild = pcntl_wait($status, WUNTRACED);
			$this->children->remove($exitedChild);
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

}
