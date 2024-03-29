<?php

namespace Mikulas\TestWorkers;

use PHP_CodeCoverage_Driver_PHPDBG as PhpDbgDriver;


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
	 * @param string $file
	 * @return string[] [string $fqn]
	 */
	public function covers(string $file)
	{
		$parser = new AnnotationParser();
		$annotations = $parser->getAnnotations($file);

		if (!isset($annotations['covers']) && !isset($annotations['coversNothing'])) {
			echo "'$file' does not have @covers or @coversNothing annotation\n";
			return [];
		}

		if (isset($annotations['coversNothing'])) {
			return [];
		}

		$covers = [];
		foreach ($annotations['covers'] as $name) {
			$covers[] = $parser->toFqn($file, $name);
		}
		return $covers;
	}


	/**
	 * @param callable      $cb
	 * @param string|string $testId file name or other unique identificator
	 * @param array         $covers [string $fqn]
	 * @return mixed output of $cb
	 */
	public function collect(callable $cb, string $testId, $covers = [])
	{
		$driver = new PhpDbgDriver();
		$driver->start();

		try {
			return $cb();

		} finally {
			$coverage = $driver->stop();
			$this->mutex->synchronized(__CLASS__, function() use ($coverage, $testId, $covers) {
				$coverages = $this->data->get();

				assert(!isset($coverages[$testId]), "TestId '$testId' is not unique");
				$coverages[$testId] = [$coverage, $covers];
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
