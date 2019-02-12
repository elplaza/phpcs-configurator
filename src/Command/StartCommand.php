<?php

namespace Elplaza\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    protected static $defaultName = "start";

    protected function configure()
    {
	    $this
	    	->setName("start")
	        ->setDescription("Configure your PHP coding standard.")
/*            ->addArgument()
            ->addOption()
*/	        ->setHelp("This command allows you to create a custom PHP coding standard.")
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
        } else {
        	$output->writeln('<error>✗</error> No sniffs can be found.');
        }
    }
}