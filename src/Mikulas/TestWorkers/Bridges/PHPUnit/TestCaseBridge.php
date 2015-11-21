<?php

namespace Mikulas\TestWorkers\Bridges\PHPUnit;

use PHPUnit_Framework_TestCase as TestCase;


class TestCaseBridge extends TestCase
{

	/** @var int */
	private $status;


	/**
	 * @param string  $testId
	 * @param int     $status
	 */
	public function __construct($testId, $status)
	{
		parent::__construct($testId);
		$this->status = $status;
	}


	/**
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

}
