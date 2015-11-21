<?php


class PhpUnitCoverageFactory
{

	/**
	 * @param array [fileId => coverages]
	 * @return PHP_CodeCoverage
	 */
	public static function create(array $coverages)
	{
		$filter = new PHP_CodeCoverage_Filter();
		$filter->addDirectoryToWhitelist(dirname(__DIR__) . '/src'); // TODO

		$coverage = new PHP_CodeCoverage(new PHP_CodeCoverage_Driver_PHPDBG(), $filter);
		$coverage->setForceCoversAnnotation(FALSE);
		$coverage->setCheckForUnintentionallyCoveredCode(FALSE);

		foreach ($coverages as $testId => list($status, $lines)) {
			$case = new PHPUnit_Framework_TestCase_Bridge($testId, $status);
			$coverage->append($lines, $case, TRUE);
		}

		return $coverage;
	}

}
