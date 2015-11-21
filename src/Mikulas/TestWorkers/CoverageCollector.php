<?php

namespace Mikulas\TestWorkers;

use Nette\Reflection\AnnotationsParser;
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
	 * @param string $file
	 * @return string[] [string $file => int[] $lines]
	 */
	public function covers(string $file)
	{
		$annotations = $this->getAnnotations($file);
		if (!isset($annotations['covers']) && !isset($annotations['coversNothing'])) {
			echo "'$file' does not have @covers annotation\n";
			return [];
		}

		if ($annotations['coversNothing']) {
			return [];
		}

		$covers = [];
		foreach ($annotations['covers'] as $name) {
			AnnotationsParser::expandClassName(); // TODO
		}

		return [];
	}


	/**
	 * @param string $file
	 * @return array
	 */
	protected function getAnnotations(string $file)
	{
		$content = file_get_contents($file);
		$tokens = token_get_all($content);
		$comments = array_filter($tokens, function($token) {
			return is_array($token) && $token[0] === T_DOC_COMMENT;
		});

		if (!$comments) {
			return [];
		}

		$docblock = array_shift($comments)[1];

		$annotations = [];
		// Strip away the docblock header and footer to ease parsing of one line annotations
		$docblock = substr($docblock, 3, -2);

		if (preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docblock, $matches)) {
			$numMatches = count($matches[0]);

			for ($i = 0; $i < $numMatches; ++$i) {
				$annotations[$matches['name'][$i]][] = $matches['value'][$i];
			}
		}

		return $annotations;
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
