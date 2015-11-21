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


	public function getVersion()
	{
		return '1.0.0';
	}


	public function getName()
	{
		return 'Test Workers';
	}


	public function getLongVersion()
	{
		return "<info>{$this->getName()}</info> {$this->getVersion()}";
	}


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
