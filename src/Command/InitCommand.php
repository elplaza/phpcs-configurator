<?php

namespace Elplaza\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
	protected static $defaultName = "init";

	protected function configure()
	{
		$this
			->setName("init")
			->setDescription("Configure your PHP coding standard.")
/*            ->addArgument()
			->addOption()
*/	        ->setHelp("This command allows you to initialize your custom PHP coding standard.")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$sniffs = array("AAA", "BBB", "CCC");
		if (!empty($sniffs)) {
			$output->writeln(
				sprintf(
					"There are <info>%s</info> sniffs availables",
					count($sniffs)
				)
			);

			foreach ($sniffs as $sniff) {
				$output->writeln("sniff: $sniff");
			}
//            $this->choiceSniffs($sniffs, $input, $output);
//            $this->writeRuleset($input, $output);
		} else {
			$output->writeln("<error>✗</error> No sniffs can be found.");
		}
	}

}
