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

	/** @var int */
	private $processLimit;

	/** @var string[] */
	private $coverageModes;

	/** @var string[] filenames */
	private $whitelist;

	/** @var Mutex */
	private $mutex;

	/** @var CoverageCollector */
	private $collector;


	public function __construct(OutputInterface $output, int $processLimit)
	{
		$this->setStyles($output);

		$this->parentPID = getmypid();
		$this->output = $output;
		$this->processLimit = $processLimit;

		$this->mutex = new Mutex(sys_get_temp_dir());
		$this->collector = new CoverageCollector($this->mutex);
	}


	/**
	 * @param string[] $coverageModes
	 * @param string[] $whitelist filenames
	 */
	public function setCoverageOptions(array $coverageModes, array $whitelist = [])
	{
		$this->coverageModes = $coverageModes;
		$this->whitelist = $whitelist;
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
		global $argv, $argc;

		ini_set('zend.assertions', 1); // generate and execute code
		ini_set('assert.exception', 1); // throw exceptions

		// prevent Nette\Tester breaking up
		putenv('NETTE_TESTER_COVERAGE=');
		$_SERVER['console_argv'] = $_SERVER['argv'];
		$_SERVER['console_argc'] = $_SERVER['argc'];
		$_SERVER['argv'] = [$_SERVER['console_argv'][0]];
		$_SERVER['argc'] = 0;
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
	 * @param string $setupFile
	 */
	protected function createSetupGlobal(string $setupFile)
	{
		global $_SETUP;
		if (!is_readable($setupFile)) {
			$this->output->writeln("<error>Setup file '$setupFile' is not readable\n");
			exit(1);
		}

		$_SETUP = require_once $setupFile;
	}


	/**
	 * @param string[] $filesToRun
	 * @param string   $setupFile
	 * @return int exit code
	 */
	public function run(array $filesToRun, string $setupFile = NULL)
	{
		$this->setupEnvironment();

		if ($setupFile) {
			$this->createSetupGlobal($setupFile);
		}

		$control = new ProcessManager(getmypid(), $this->processLimit);
		$control->setDebugCallback(function($message) {
			$this->debug($message);
		});

		while ($file = array_shift($filesToRun)) {
			if ($control->fork() === ProcessManager::CHILD) {
				$this->debug("process '$file");

				$runner = new TestRunner($file, $this->mutex);
				$status = $this->coverageModes ? $runner->runWithCoverage($this->collector) : $runner->run();
				$this->mutex->synchronized(Mutex::STD_OUT, function() use ($file, $runner) {
					$this->printTestResults($file, $runner);
				});
				$this->debug("exit with '$status'");
				exit($status);

			} else {
				$this->debug("loop");
				continue;
			}
		}

		// only parent process will get here
		$this->debug("waiting for children");
		$control->waitForChildren();

		$counter = $control->getCounter();
		$this->printResults($counter);

		if ($this->coverageModes) {
			$this->generateCoverageReports();
		}

		// skip Nette\Tester processing in parent thread
		register_shutdown_function(function() {
			exit; // prevent further shutdown handlers
		});

		$this->debug("exit");
		return $counter[ProcessManager::CODE_FAIL] !== 0 ? 1 : 0;
	}


	/**
	 * @param string     $file
	 * @param TestRunner $runner
	 */
	protected function printTestResults(string $file, TestRunner $runner)
	{
		$status = $runner->getStatus();
		if ($status === ProcessManager::CODE_SUCCESS) {
			if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
				$this->output->write('.');
			} elseif ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE) {
				$this->output->writeln($file);
			}
			return;
		}

		$this->output->writeln('');
		if ($status === ProcessManager::CODE_SKIP) {
			$this->output->writeln("<fg=yellow;underscore=underscore;bold=bold>$file</> skipped");
			return;
		}

		$postfix = '';
		if ($status === ProcessManager::CODE_ERROR) {
			$postfix = '<bg=red>errored</>';
		}

		$this->output->writeln("<test-case>$file</test-case> $postfix");
		if ($runner->getOutput()) {
			$this->output->writeln($runner->getOutput());
		}
		if ($e = $runner->getError()) {
			$this->output->writeln('<error>' . get_class($e) . ': ' . $e->getMessage() . '</error>');
			$this->output->writeln("\n<trace>" . $e->getTraceAsString() . "</trace>");
		}
		$this->output->writeln('');
	}


	protected function generateCoverageReports()
	{
		$this->output->writeln("Generating code coverage report");

		$factory = new PhpUnitCoverageFactory($this->whitelist);
		$phpUnitCoverage = $factory->create($this->collector->getCoverages());
		$this->collector->destroy();

		foreach ($this->coverageModes as $mode => $option) {
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


	private function printResults($counter)
	{
		$total = array_sum($counter);
		$success = $counter[ProcessManager::CODE_SUCCESS];
		$failed = $counter[ProcessManager::CODE_FAIL];
		$skipped = $counter[ProcessManager::CODE_SKIP];
		$errored = $counter[ProcessManager::CODE_ERROR];

		$s = function($count) {
			return $count > 1 ? 's' : '';
		};

		$this->output->write("\n");

		if ($errored) {
			$this->output->writeln("<fg=red;bold=bold>$errored test{$s($errored)} errored</>");
		}

		if ($failed) {
			$this->output->writeln("<error>$failed test{$s($failed)} failed / $total total</error>");
		} else {
			$this->output->writeln("<info>$success test{$s($success)} succeeded</info>");
		}

		if ($skipped) {
			$this->output->writeln("<comment>$skipped test{$s($failed)} skipped</comment>");
		}

		$this->output->writeln('');
	}

}
