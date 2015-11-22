<?php

namespace Mikulas\TestWorkers\Bridges\SymfonyConsole;

use Mikulas\TestWorkers\Controller;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Command extends Console\Command\Command
{

	protected function configure()
	{
		$this
			->addOption('process-limit', 'p', InputOption::VALUE_OPTIONAL, 'Max number of process to run concurrently', 50)
			->addOption('coverage-clover', NULL, InputOption::VALUE_REQUIRED, 'Generate code coverage report in Clover XML format.')
			->addOption('coverage-crap4j', NULL, InputOption::VALUE_REQUIRED, 'Generate code coverage report in Crap4J XML format.')
			->addOption('coverage-html', NULL, InputOption::VALUE_REQUIRED, 'Generate code coverage report in HTML format.')
			->addOption('whitelist', 'w', InputOption::VALUE_OPTIONAL| InputOption::VALUE_IS_ARRAY, 'Whitelist for code coverage analysis.')
			->addOption('setup', NULL, InputOption::VALUE_REQUIRED, 'Php file to require once for all tests')
			->addArgument('files', InputArgument::IS_ARRAY, 'Files to execute');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$files = $input->getArgument('files');
		if (!$files) {
			$input = new Console\Input\ArrayInput(['--help']);
			$this->getApplication()->run($input, $output);
			return 0;
		}

		$modeMap = [
			'coverage-clover' => Controller::COVERAGE_CLOVER,
			'coverage-crap4j' => Controller::COVERAGE_CRAP4J,
			'coverage-html' => Controller::COVERAGE_HTML,
		];

		$coverageModes = [];
		foreach ($modeMap as $option => $mode) {
			if ($file = $input->getOption($option)) {
				$coverageModes[$mode] = $file;
			}
		}

		$controller = new Controller($output, $input->getOption('process-limit'));
		$controller->setCoverageOptions($coverageModes, $input->getOption('whitelist'));
		return $controller->run($files, $input->getOption('setup'));
	}

}
