<?php

namespace Elplaza\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Util\Standards;
use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Generators\Text;

use Elplaza\TextGenerator;

class InitCommand extends Command
{
	protected static $defaultName = "init";

	const STANDARD_DEFAULT_NAME = "MyPhpcs";
	const STANDARD_DEFAULT_PATH = DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "rulesets";

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
		$io = new SymfonyStyle($input, $output);

		$io->info("Let's start to initialize your PHP Coding Standard!");

		$helper = $this->getHelper("question");

		// ask name of custom coding standard
		$name = $helper->ask($input, $output, $this->questionName());

		$path = $helper->ask($input, $output, $this->questionPath());

		$fullpath = $path . DIRECTORY_SEPARATOR . $name;

		// create coding standard directory
		if (file_exists($fullpath)) {
			$message = "Directory $fullpath <comment>already exists</comment>,"
				. " you want override (default is NOT)? "
			;
			$question = new ConfirmationQuestion($message, false);

			if (!$helper->ask($input, $output, $question)) {
				$io->error("Your PHP Coding Standard has not been initialized.");
				return self::FAILURE;
			}

			$this->rrmdir($fullpath);
		}

		if (!mkdir($fullpath, 0777, true)) {
			$io->error("Failed to create directory $fullpath.");
			return self::FAILURE;			
		}

		// create the ruleset.xml
		$sniffs = $this->getAllSniffs();
		if (!empty($sniffs)) {
			$io->info(
				sprintf(
					"There are %s sniffs availables",
					count($sniffs)
				)
			);

			$sniffsChoiced = array();
			foreach ($sniffs as $sniff) {
				$pos = strpos($sniff["code"], "Zend");
				if ($pos === false) {
					continue;
				}

				$io->newLine();
				$io->text("##############################################################");
				$io->newLine();

				if (!empty($sniff["doc"])) {
					if (!empty($sniff["generator"])) {
						$io->text($sniff["generator"]->generate());
					}
				}

				$message = sprintf(
					"Do you want to enable <info>%s</info> sniff? (y/n) ",
					$sniff["code"]
				);

				$question = new ConfirmationQuestion($message, false);

				$sniff["choice"] = $helper->ask($input, $output, $question);
				if ($sniff["choice"]) {
					$sniffsChoiced[] = $sniff;
				}
			}

            $this->writeRuleset($sniffsChoiced, $fullpath);
			
			$io->success("$name Coding Standard ruleset is successfully created in $fullpath");

			return self::SUCCESS;
		} else {
			$io->error("No sniffs can be found.");
		}

		return self::FAILURE;
	}

	private function writeRuleset($sniffs, $fullpath)
	{
		$file = $fullpath . DIRECTORY_SEPARATOR . "ruleset.xml";

		$parts = explode(DIRECTORY_SEPARATOR, $fullpath);
		$name  = end($parts);

		$dom = new \DOMDocument();
		$dom->formatOutput = true;
		$dom->xmlVersion = "1.0";
		$ruleset = $dom->createElement("ruleset");
		$ruleset->setAttributeNode(new \DOMAttr("name", $name));

		foreach ($sniffs as $sniff) {
			$rule = $dom->createElement("rule");
			$rule->setAttributeNode(new \DOMAttr("ref", $sniff["code"]));
			$ruleset->appendChild($rule);
		}

		$dom->appendChild($ruleset);

		$dom->save($file);
	}

	private function getAllSniffs()
	{
		$sniffs    = array();
//		$standards = Standards::getInstalledStandards();
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

		$tokens = new Tokens();

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
/*		$pos = strpos($sniffFile, "ClassDeclaration");
		if ($pos !== false) {
			die(var_dump($sniffFile, $docFile, is_file($docFile)));
		}*/
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

			$s = array(
				"standard"  => $standard,
				"code"      => $code,
				"path"      => $path,
				"doc"       => $doc,
			);

			if (!empty($doc)) {
				$s["generator"] = $this->getGenerator($standard)->setDocFiles(array($doc));
			}

			$sniffs[] = $s;
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
			self::STANDARD_DEFAULT_NAME
		);

		$question = new Question($message, self::STANDARD_DEFAULT_NAME);
		$question->setNormalizer(
			function ($value) {
				return $value ? trim($value) : "";
			}
		);

		return $question;
	}

	private function questionPath()
	{
		$default = DIRECTORY_SEPARATOR . "app";

		$message = sprintf(
			"Please enter the <info>ABSOLUTE PATH DESTINATION DIRECTORY</info> (default is %s): ",
			self::STANDARD_DEFAULT_PATH
		);

		$question = new Question($message, self::STANDARD_DEFAULT_PATH);
		$question
			->setNormalizer(
				function ($value) {
					$dir = $value ? trim($value) : "";
					return rtrim($dir, DIRECTORY_SEPARATOR);
				}
			)
			->setValidator(
				function ($value) {
					if (empty($value) || !is_string($value)) {
						throw new \RuntimeException(
							"Please enter a directory path"
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
