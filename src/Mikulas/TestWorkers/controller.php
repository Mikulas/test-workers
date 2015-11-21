<?php

namespace Mikulas\TestWorkers;

use PHP_CodeCoverage_Report_Clover;
use PHP_CodeCoverage_Report_Crap4j;
use PHP_CodeCoverage_Report_HTML;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;


class Controller
{

	const COVERAGE_CLOVER = 1;
	const COVERAGE_HTML = 2;
	const COVERAGE_CRAP4J = 3;

	/** @var int */
	private $parentPID;

	/** @var OutputInterface */
	private $output;


	public function __construct(OutputInterface $output)
	{
		$this->setStyles($output);

		$this->parentPID = getmypid();
		$this->output = $output;
	}


	protected function setStyles(OutputInterface $output)
	{
		$child = new OutputFormatterStyle('yellow');
		$output->getFormatter()->setStyle('debug-parent', $child);
		$parent = new OutputFormatterStyle('cyan');
		$output->getFormatter()->setStyle('debug-child', $parent);

		$test = new OutputFormatterStyle('red', NULL, ['underscore', 'bold']);
		$output->getFormatter()->setStyle('test-case', $test);

		$test = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('error', $test);

		$test = new OutputFormatterStyle('black');
		$output->getFormatter()->setStyle('trace', $test);
	}


	/**
	 * @return void
	 */
	protected function setupEnvironment()
	{
		ini_set('zend.assertions', 1); // generate and execute code
		ini_set('assert.exception', 1); // throw exceptions
	}


	/**
	 * Prints to OutputInterface if verbosity is high enough
	 * @param string $message
	 * @return void
	 */
	protected function debug($message)
	{
		if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
			$pid = getmypid();
			$prefix = ($pid === $this->parentPID ? "<debug-parent>$pid parent:</debug-parent>" : "<debug-child>$pid child:</debug-child>");
			$this->output->writeln("$prefix $message");
		}
	}


	/**
	 * @param string[] $filesToRun
	 * @param int      $processLimit
	 * @param string[] $coverageModes
	 * @return int exit code
	 */
	public function run(array $filesToRun, int $processLimit, array $coverageModes = [])
	{
		$this->setupEnvironment();

		$control = new ProcessManager(getmypid(), $processLimit);
		$control->setDebugCallback(function($message) {
			$this->debug($message);
		});

		$mutex = new Mutex(sys_get_temp_dir());
		$collector = new CoverageCollector($mutex);

		while ($file = array_shift($filesToRun)) {
			if ($control->fork() === ProcessManager::CHILD) {
				$this->debug("process '$file");
				ob_start();

				$error = NULL;
				try {
					if ($coverageModes) {
						$covers = $collector->covers($file);
						echo "this file covers:\n";
						var_dump($covers);

						$collector->collect(function() use ($file) {
							require_once $file;
						}, $file);

					} else {
						require_once $file;
					}
				} catch (\Error $error) {
				} finally {
					$output = ob_get_clean();
					$mutex->synchronized(Mutex::STD_OUT, function() use ($file, $output) {
						if ($output) {
							$this->output->writeln("<test-case>$file</test-case>");
							$this->output->writeln($output);
						}
					});
					$this->debug("exit");

					if ($error !== NULL) {
						$mutex->synchronized(Mutex::STD_OUT, function() use ($file, $error) {
							$this->output->writeln("<test-case>$file</test-case>");
							$this->output->writeln('-  <error>' . get_class($error) . ': ' . $error->getMessage() . '</error>');
							$this->output->writeln('<trace>' . $error->getTraceAsString() . '</trace>');
						});
						exit(1);
					}
					exit(0);
				}

			} else {
				$this->debug("loop");
				continue;
			}
		}

		// only parent will get here
		$control->waitForChildren();

		$counter = $control->getCounter();
		$this->printResults($counter);

		if ($coverageModes) {
			echo "Generating code coverage report\n";

			$phpUnitCoverage = PhpUnitCoverageFactory::create($collector->getCoverages());
			$collector->destroy();

			foreach ($coverageModes as $mode => $option) {
				switch ($mode) {
					case self::COVERAGE_CLOVER:
						$writer = new PHP_CodeCoverage_Report_Clover();
						$writer->process($phpUnitCoverage, $option);
						$this->output->writeln("Clover coverage report generated to '<comment>$option</comment>'");
						break;
					case self::COVERAGE_CRAP4J:
						$writer = new PHP_CodeCoverage_Report_Crap4j();
						$writer->process($phpUnitCoverage, $option);
						$this->output->writeln("Crap4j coverage report generated to '<comment>$option</comment>'");
						break;
					case self::COVERAGE_HTML:
						$writer = new PHP_CodeCoverage_Report_HTML();
						$writer->process($phpUnitCoverage, $option);
						$this->output->writeln("Html coverage report generated to '<comment>$option</comment>'");
						break;
					default:
						assert(FALSE, "Unsupported mode '$mode'");
				}
			}
		}

		$this->debug("exit");
		return $counter[ProcessManager::CODE_FAIL] !== 0 ? 1 : 0;
	}


	private function printResults($counter)
	{
		$total = array_sum($counter);
		$success = $counter[ProcessManager::CODE_SUCCESS];
		$failed = $counter[ProcessManager::CODE_FAIL];
		$skipped = $counter[ProcessManager::CODE_SKIP];

		$s = function($count) {
			return $count > 1 ? 's' : '';
		};

		$this->output->write("\n");
		if ($failed) {
			$this->output->writeln("<error>$failed test{$s($failed)} failed / $total total</error>");
		} else {
			$this->output->writeln("<info>$success test{$s($success)} succeeded</info>");
		}

		if ($skipped) {
			$this->output->writeln("<comment>$skipped test{$s($failed)} skipped</comment>");
		}
	}

}
