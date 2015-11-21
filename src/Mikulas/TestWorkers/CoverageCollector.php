<?php

namespace Mikulas\TestWorkers;

use PHP_CodeCoverage_Driver_PHPDBG as PhpDbgDriver;
use PHPUnit_Runner_BaseTestRunner as TestRunner;


class CoverageCollector
{

	/** @var Mutex */
	private $mutex;

	/** @var ISharedVariable of [mixed $fileId => [$status, $coverage]] */
	private $data;


	public function __construct(Mutex $mutex)
	{
		$this->mutex = $mutex;
		$this->data = new SharedFileVariable($mutex, []);
	}


	/**
	 * @param callable $cb
	 * @param string   $testId file name or other unique identificator
	 */
	public function collect(callable $cb, $testId)
	{
		$driver = new PhpDbgDriver();
		$driver->start();

		try {
			$cb();
			$status = TestRunner::STATUS_PASSED;

		} catch (\AssertionError $e) {
			$status = TestRunner::STATUS_FAILURE;
			throw $e;

		} catch (\Error $e) {
			$status = TestRunner::STATUS_ERROR;
			throw $e;

		} finally {
			$coverage = $driver->stop();
			$this->mutex->synchronized(__CLASS__, function() use ($coverage, $testId, $status) {
				$coverages = $this->data->get();

				assert(!isset($coverages[$testId]), "TestId '$testId' is not unique");
				$coverages[$testId] = [$status, $coverage];
				$this->data->set($coverages);
			});
		}

		return $status;
	}


	/**
	 * @return array [testId => [status, coverage]]
	 */
	public function getCoverages()
	{
		return $this->data->get();
	}


	/**
	 * @return void
	 */
	public function destroy()
	{
		$this->data->destroy();
	}

}
