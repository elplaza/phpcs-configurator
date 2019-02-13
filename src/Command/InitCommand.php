<?php

namespace Elplaza\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InitCommand extends Command
{
	protected static $defaultName = "init";

	const PHPCS_DEFAULT_NAME = "MyPhpcs";

	protected function configure()
	{
		$this
			->setName("init")
			->setDescription("Configure your PHP coding standard.")
			->setHelp("This command allows you to initialize your custom PHP coding standard.")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln("<info>Let's start to initialize your PHP Coding Standard!</info>");

		$helper = $this->getHelper("question");

		// ask name of custom coding standard
		$name = $helper->ask($input, $output, $this->questionName());

		// ask path of custom coding standard
		$path = $helper->ask($input, $output, $this->questionPath());

		$fullpath = $path . DIRECTORY_SEPARATOR . $name;

		// check if already exists
		if (file_exists($fullpath)) {
			$question = new ConfirmationQuestion(
				"Directory $fullpath already exists, you want override (default is NOT)? ",
				false
			);

			if (!$helper->ask($input, $output, $question)) {
				$output->writeln(
					"<comment>Your PHP Coding Standard has not been initialized.</comment>"
				);
				return;
			}

			$this->rrmdir($fullpath);
		}

		mkdir($fullpath);

		die(var_dump($fullpath));

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

	private function questionName()
	{
		$message = sprintf(
			"Please enter the NAME of your php coding standard (default is %s): ",
			self::PHPCS_DEFAULT_NAME
		);

		$question = new Question($message, self::PHPCS_DEFAULT_NAME);
		$question->setNormalizer(
			function ($value) {
				return $value ? trim($value) : "";
			}
		);

		return $question;
	}

	private function questionPath()
	{
		$question = new Question("Please enter the ABSOLUTE PATH DESTINATION DIRECTORY: ");
		$question
			->setNormalizer(
				function ($value) {
					$dir = $value ? trim($value) : "";
					return rtrim($dir, DIRECTORY_SEPARATOR);
				}
			)
			->setValidator(
				function ($value) {
					if (
						empty($value)
						|| !is_string($value)
						|| !is_dir($value)
						|| !is_writable($value)
					) {
						throw new \RuntimeException(
							"Please enter an existing writable directory path"
						);
					}

					return $value;
				}
			)
		;

		return $question;
	}

	// delete recursively a directory
	private function rrmdir($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					$path = $dir . DIRECTORY_SEPARATOR . $object;
					if (is_dir($path)) {
						$this->rrmdir($path);
					} else {
						unlink($path);
					}
				}
			}

			reset($objects);
			rmdir($dir);
		}
	}

}
