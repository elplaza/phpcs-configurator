<?php

namespace Elplaza;

use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Generators\Text;

class TextGenerator extends Text
{

	public function __construct(Ruleset $ruleset)
	{
		$this->ruleset  = $ruleset;
		$this->docFiles = array();
	}

	public function setDocFiles(array $files)
	{
		$this->docFiles = $files;
		return $this;
	}
/*
	public function processSniff(\DOMNode $doc)
	{
		if (PHPCSC_VERBOSITY === 0) {
			$this->printTitle($doc);
		} else {
			foreach ($doc->childNodes as $node) {
				if (PHPCSC_VERBOSITY === 1 && $node->nodeName === "standard") {
					$this->printTextBlock($node);
				} elseif (PHPCSC_VERBOSITY === 2 && $node->nodeName === "code_comparison") {
					$this->printCodeComparisonBlock($node);
				}
			}
		}
	}
*/
}
