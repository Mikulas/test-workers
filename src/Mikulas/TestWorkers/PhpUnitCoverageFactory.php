<?php

namespace Mikulas\TestWorkers;

use Mikulas\TestWorkers\Bridges\PHPUnit\TestCaseBridge;
use PHP_CodeCoverage as CodeCoverage;
use PHP_CodeCoverage_Filter as CodeCoverageFilter;


class PhpUnitCoverageFactory
{

	/** @var string[] */
	private $whitelist;


	/**
	 * @param string[] $whitelist
	 */
	public function __construct(array $whitelist)
	{
		$this->whitelist = $whitelist;
	}


	/**
	 * @param array [fileId => coverages]
	 * @return CodeCoverage
	 */
	public function create(array $coverages)
	{
		$filter = new CodeCoverageFilter();
		foreach ($this->whitelist as $item) {
			if (is_dir($item)) {
				$filter->addDirectoryToWhitelist($item);
			} else {
				$filter->addFileToWhitelist($item);
			}
		}

		$coverage = new CodeCoverage(NULL, $filter);
		$coverage->setForceCoversAnnotation(FALSE);
		$coverage->setCheckForUnintentionallyCoveredCode(FALSE);

		foreach ($coverages as $testId => list($status, $lines, $covers)) {
			$linesToBeCovered = $this->getCoveredLines($covers);
			$case = new TestCaseBridge($testId, $status);
			$coverage->append($lines, $case, TRUE, $linesToBeCovered);
		}

		return $coverage;
	}


	/**
	 * @param string[] $covers FQNs
	 * @return array [string $filename => [int $line => bool $cover]]
	 */
	private function getCoveredLines(array $covers)
	{
		$result = [];

		foreach ($covers as $fqn) {
			$fqn = preg_replace('~\(.*\)$~', '', $fqn);

			try {
				$ref = strpos($fqn, '::') !== NULL ? new \ReflectionMethod($fqn) : new \ReflectionClass($fqn);
			} catch (\ReflectionException $e) {
				echo "\e[31mInvalid @covers annotation: {$e->getMessage()}\e[0m\n";
				// TODO HANDLE PROPERLY
				continue;
			}

			for ($line = $ref->getStartLine(); $line < $ref->getEndLine(); ++$line) {
				$result[$ref->getFileName()][$line] = TRUE;
			}
 		}

		return $result;
	}

}
