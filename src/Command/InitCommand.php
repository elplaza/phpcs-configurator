<?php

namespace Elplaza\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Util\Standards;
use PHP_CodeSniffer\Generators\Text;

use Elplaza\TextGenerator;

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

		// create coding standard directory
		if (file_exists($fullpath)) {
			$message = "Directory $fullpath <comment>already exists</comment>,"
				. " you want override (default is NOT)? "
			;
			$question = new ConfirmationQuestion($message, false);

			if (!$helper->ask($input, $output, $question)) {
				$output->writeln(
					"<comment>Your PHP Coding Standard has not been initialized.</comment>"
				);
				return;
			}

			$this->rrmdir($fullpath);
		}

		mkdir($fullpath);

		// create the ruleset.xml
		$sniffs = $this->getAllSniffs();
		if (!empty($sniffs)) {
			$output->writeln(
				sprintf(
					"There are <info>%s</info> sniffs availables",
					count($sniffs)
				)
			);

			foreach ($sniffs as $sniff) {
				$generator = $sniff["generator"];

				$output->writeln($generator->generate());

				$question = new ConfirmationQuestion("Do you want to enable this sniff? ", false);

				$sniff["choice"] = $helper->ask($input, $output, $question);
			}

		//	$xml = new SimpleXMLElement($xmlstr);

//            $this->writeRuleset($input, $output, $choices);
		} else {
			$output->writeln("<error>✗</error> No sniffs can be found.");
		}

//			$generator->generate();
//		die(var_dump(count($sniffs)));

		// add all coding standard needs

		// create another command to create custom sniffs
		// ask for custom sniff: if yes create directories and files

	}

	private function getAllSniffs()
	{
		$sniffs    = array();
		$standards = Standards::getInstalledStandards(true);
		foreach ($standards as $standard) {
			$sniffs = array_merge($sniffs, $this->getStandardInfo($standard));
		}

		return $sniffs;
	}

	private function getGenerator($standard)
	{
		$config = new Config(array(), false);
		$config->standards = array($standard);
		$ruleset = new Ruleset($config);
		$ruleset->name = $standard;

		return new TextGenerator($ruleset);
	}

	private function getSniffCode($sniffFile)
	{
		$sniffPart = str_replace("Sniff.php", "", $sniffFile);
		$exploded  = explode(DIRECTORY_SEPARATOR, $sniffPart);
		$parts     = array_slice($exploded, -4);

		unset($parts[1]);

		return implode(".", $parts);
	}

	private function getSniffDoc($sniffFile)
	{
		$docFile = str_replace(
			DIRECTORY_SEPARATOR . "Sniffs" . DIRECTORY_SEPARATOR,
			DIRECTORY_SEPARATOR . "Docs" . DIRECTORY_SEPARATOR,
			$sniffFile
		);

		$docFile = str_replace("Sniff.php", "Standard.xml", $docFile);
		return (is_file($docFile)) ? $docFile : null;
	}

	private function sniffDirPath($standard)
	{
		$rulesetPath  = Standards::getInstalledStandardPath($standard);
		$standardPath = str_replace("ruleset.xml", "", $rulesetPath);
		return $standardPath . "Sniffs" . DIRECTORY_SEPARATOR;
	}

	private function getStandardInfo($standard)
	{
		$generator = $this->getGenerator($standard);
		$directory = $this->sniffDirPath($standard);

		$sniffs = array();

		$rdi = new \RecursiveDirectoryIterator(
			$directory,
			\RecursiveDirectoryIterator::FOLLOW_SYMLINKS
		);

		$di  = new \RecursiveIteratorIterator($rdi, 0, \RecursiveIteratorIterator::CATCH_GET_CHILD);

		$dirLen = strlen($directory);

		foreach ($di as $file) {
			$filename = $file->getFilename();

			// Skip hidden files.
			if (substr($filename, 0, 1) === ".") {
				continue;
			}

			// We are only interested in PHP and sniff files.
			$fileParts = explode(".", $filename);
			if (array_pop($fileParts) !== "php") {
				continue;
			}

			$basename = basename($filename, ".php");
			if (substr($basename, -5) !== "Sniff") {
				continue;
			}

			$path = $file->getPathname();

			// Skip files in hidden directories within the Sniffs directory of this
			// standard. We use the offset with strpos() to allow hidden directories
			// before, valid example:
			// /home/foo/.composer/vendor/squiz/custom_tool/MyStandard/Sniffs/...
			if (strpos($path, DIRECTORY_SEPARATOR . ".", $dirLen) !== false) {
				continue;
			}

			$code = $this->getSniffCode($path);
			$doc  = $this->getSniffDoc($path);

			$generator->setDocFiles(array($doc));

			$sniffs[] = array(
				"standard"  => $standard,
				"code"      => $code,
				"path"      => $path,
				"doc"       => $doc,
				"generator" => $generator
			);
		}

		return $sniffs;
	}

	private function askSniff(
		InputInterface $input,
		OutputInterface $output,
		$sniff,
		$verbose = false
	) {
	}

	private function questionName()
	{
		$message = sprintf(
			"Please enter the <info>NAME</info> of your php coding standard (default is %s): ",
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
		$question = new Question(
			"Please enter the <info>ABSOLUTE PATH DESTINATION DIRECTORY</info>: "
		);
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
