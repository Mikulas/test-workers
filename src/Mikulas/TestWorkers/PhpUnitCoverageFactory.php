<?php

namespace Mikulas\TestWorkers;

use Mikulas\TestWorkers\Bridges\PHPUnit\TestCaseBridge;
use PHP_CodeCoverage as CodeCoverage;
use PHP_CodeCoverage_Filter as CodeCoverageFilter;


class PhpUnitCoverageFactory
{

	/**
	 * @param array [fileId => coverages]
	 * @return CodeCoverage
	 */
	public static function create(array $coverages)
	{
		$filter = new CodeCoverageFilter();
		$filter->addDirectoryToWhitelist(dirname(__DIR__) . '/src'); // TODO

		$coverage = new CodeCoverage(NULL, $filter);
		$coverage->setForceCoversAnnotation(FALSE);
		$coverage->setCheckForUnintentionallyCoveredCode(FALSE);

		foreach ($coverages as $testId => list($status, $lines)) {
			$case = new TestCaseBridge($testId, $status);
			$coverage->append($lines, $case, TRUE);
		}

		return $coverage;
	}

}
