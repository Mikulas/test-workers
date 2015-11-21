<?php

namespace Mikulas\TestWorkers\Bridges\SymfonyConsole;

use Mikulas\TestWorkers\Controller;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Command extends Console\Command\Command
{

	protected function configure()
	{
		$this
			->addOption('process-limit', 'p', Console\Input\InputOption::VALUE_OPTIONAL, 'Max number of process to run concurrently', 50)
			->addOption('coverage-clover', NULL, Console\Input\InputOption::VALUE_REQUIRED, 'Generate code coverage report in Clover XML format.')
			->addOption('coverage-crap4j', NULL, Console\Input\InputOption::VALUE_REQUIRED, 'Generate code coverage report in Crap4J XML format.')
			->addOption('coverage-html', NULL, Console\Input\InputOption::VALUE_REQUIRED, 'Generate code coverage report in HTML format.')
			->addOption('whitelist', 'w', Console\Input\InputOption::VALUE_OPTIONAL|Console\Input\InputOption::VALUE_IS_ARRAY, 'Whitelist for code coverage analysis.')
			->addArgument('files', Console\Input\InputArgument::IS_ARRAY, 'Files to execute');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
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
		return $controller->run($input->getArgument('files'), $input->getOption('whitelist'), $coverageModes);
	}

}
