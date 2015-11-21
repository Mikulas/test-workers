<?php

class CoverageCollector
{

	/** @var Mutex */
	private $mutex;

	/** @var ISharedVariable of [mixed $fileId => [$status, $coverage]] */
	private $data;


	public function __construct(Mutex $mutex)
	{
		$this->mutex = $mutex;
//		$this->data = new SharedMemoryVariable($mutex, 50000, []); // TODO fix size
		$this->data = new SharedFileVariable($mutex, []);
	}


	/**
	 * @param callable $cb
	 * @param string   $testId file name or other unique identificator
	 */
	public function collect(callable $cb, $testId)
	{
		$driver = new PHP_CodeCoverage_Driver_PHPDBG();
		$driver->start();

		try {
			$status = PHPUnit_Runner_BaseTestRunner::STATUS_PASSED;
			$cb();

		} catch (AssertionError $e) {
			$status = PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE;

		} catch (Error $e) {
			$status = PHPUnit_Runner_BaseTestRunner::STATUS_ERROR;

		} finally {
			$coverage = $driver->stop();
			$this->mutex->synchronized(__CLASS__, function() use ($coverage, $testId, $status) {
				$coverages = $this->data->get();

				assert(!isset($coverages[$testId]), "TestId '$testId' is not unique");
				$coverages[$testId] = [$status, $coverage];
				$this->data->set($coverages);
			});
		}
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
