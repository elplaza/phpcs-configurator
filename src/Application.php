<?php

namespace Elplaza;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{

	const LOGO = <<<LOGO
    ____   __  __ ____   ______ _____    ______               ____ _                             __
   / __ \ / / / // __ \ / ____// ___/   / ____/____   ____   / __/(_)____ _ __  __ _____ ____ _ / /_ ____   _____
  / /_/ // /_/ // /_/ // /     \__ \   / /    / __ \ / __ \ / /_ / // __ `// / / // ___// __ `// __// __ \ / ___/
 / ____// __  // ____// /___  ___/ /  / /___ / /_/ // / / // __// // /_/ // /_/ // /   / /_/ // /_ / /_/ // /
/_/    /_/ /_//_/     \____/ /____/   \____/ \____//_/ /_//_/  /_/ \__, / \__,_//_/    \__,_/ \__/ \____//_/
                                                                  /____/
LOGO;

	public function __construct(string $name = "UNKNOWN", string $version = "UNKNOWN")
	{
		$this->setName($name);
		$this->setVersion($version);

		parent::__construct($name, $version);
	}

	protected function configureIO(InputInterface $input, OutputInterface $output)
	{
		parent::configureIO($input, $output);

		$output->writeln(self::LOGO);
	}

}
