<?php

namespace Mikulas\TestWorkers\Bridges\SymfonyConsole;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;


/**
 * Single command application
 */
class Application extends Console\Application
{

	const DEFAULT_COMMAND = 'command';

	protected function getCommandName(InputInterface $input)
	{
		return self::DEFAULT_COMMAND;
	}


	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();
		$commands[] = new Command(self::DEFAULT_COMMAND);
		return $commands;
	}


	public function getDefinition()
	{
		$definition = parent::getDefinition();
		$definition->setArguments();
		return $definition;
	}

}
