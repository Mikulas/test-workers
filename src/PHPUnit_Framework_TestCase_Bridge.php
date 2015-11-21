<?php


class PHPUnit_Framework_TestCase_Bridge extends PHPUnit_Framework_TestCase
{

	/** @var int */
	private $status;


	public function __construct($testId, $status)
	{
		parent::__construct($testId);
		$this->status = $status;
	}


	public function getStatus()
	{
		return $this->status;
	}


}
