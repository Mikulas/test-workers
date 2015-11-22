<?php

namespace Mikulas\TestWorkers;


class TestRunner
{

	/** @var Mutex */
	private $mutex;

	/** @var bool */
	private $executed = FALSE;

	/** @var string */
	private $file;

	/** @var \Error */
	private $error;

	/** @var string */
	private $output;

	/** @var int */
	private $status;


	public function __construct(string $file, Mutex $mutex)
	{
		$this->mutex = $mutex;
		$this->file = $file;

		assert(is_readable($this->file), "Test '{$this->file}' is not readable");
	}


	public function runWithCoverage(CoverageCollector $collector)
	{
		$covers = $collector->covers($this->file);
		return $collector->collect(function() {
			return $this->run();
		}, $this->file, $covers);
	}


	/**
	 * @return int status
	 */
	public function run()
	{
		$this->executed = TRUE;

		ob_start();
		try {
			$testResponse = require_once $this->file;
			if ($testResponse === 1) { // successful require without return
				$status = ProcessManager::CODE_SUCCESS;
			} elseif (is_int($testResponse)) {
				$status = $testResponse;
			} else {
				$status = ProcessManager::CODE_SUCCESS;
			}

		} catch (\AssertionError $error) {
			$this->error = $error;
			$status = ProcessManager::CODE_FAIL;

		} catch (\Throwable $error) {
			$this->error = $error;
			$status = ProcessManager::CODE_ERROR;

		} finally {
			$this->output = ob_get_clean();
			$this->status = $status;
			return $status;
		}
	}


	/**
	 * @return \Error
	 */
	public function getError()
	{
		assert($this->executed);
		return $this->error;
	}


	/**
	 * @return string
	 */
	public function getOutput()
	{
		assert($this->executed);
		return $this->output;
	}


	/**
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

}
