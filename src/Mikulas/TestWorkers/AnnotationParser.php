<?php

namespace Mikulas\TestWorkers;


class AnnotationParser
{

	/** @var [string $file => array $tokens] */
	private $tokens = [];

	/** @var [string $file => array $usings] */
	private $usings = [];

	/** @var [string $file => string $namespace] */
	private $namespaces;


	/**
	 * @param string $file
	 * @return string[]
	 */
	public function getAnnotations(string $file)
	{
		$tokens = $this->getTokens($file);
		$comments = array_filter($tokens, function($token) {
			return is_array($token) && $token[0] === T_DOC_COMMENT;
		});

		if (!$comments) {
			return [];
		}

		$docblock = array_shift($comments)[1];

		$annotations = [];
		// Strip away the docblock header and footer to ease parsing of one line annotations
		$docblock = substr($docblock, 3, -2);

		if (preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docblock, $matches)) {
			$numMatches = count($matches[0]);

			for ($i = 0; $i < $numMatches; ++$i) {
				$annotations[$matches['name'][$i]][] = $matches['value'][$i];
			}
		}

		return $annotations;
	}


	/**
	 * @param string $file
	 * @return array
	 */
	private function getTokens($file)
	{
		if (!isset($this->tokens[$file])) {
			$this->tokens[$file] = token_get_all(file_get_contents($file));
		}
		return $this->tokens[$file];
	}


	/**
	 * @param string $file
	 * @param string $name
	 * @return string|mixed
	 */
	public function toFqn(string $file, string $name)
	{
		if ($name[0] === '\\') {
			return ltrim($name, '\\');
		}

		$usings = $this->getUsings($file);
		foreach ($usings as $use => $alias) {
			$fqn = preg_replace('~^' . preg_quote($alias, '~') . '~i', $use, $name, -1, $count);
			if ($count !== 0) {
				return ltrim($fqn, '\\');
			}
		}

		return $this->getNamespace($file) . "\\$name";
	}


	/**
	 * @param string $file
	 * @return string[]
	 */
	protected function getUsings(string $file)
	{
		if (isset($this->usings[$file])) {
			return $this->usings[$file];
		}

		$tokens = $this->getTokens($file);

		$uses = [];

		$max = count($tokens);
		$commaSeparatedUses = FALSE;
		for ($pos = 0; $pos < $max; ++$pos) {
			list($type, $content) = is_array($tokens[$pos]) ? $tokens[$pos] : [NULL, $tokens[$pos]];

			if ($type === T_USE || ($commaSeparatedUses && $content === ',')) {
				$buffer = [];
				$pos++; // skip first whitespace

				while (TRUE) {
					$pos++;
					list($_, $content) = is_array($tokens[$pos]) ? $tokens[$pos] : [NULL, $tokens[$pos]];
					if ($content === ';') {
						$commaSeparatedUses = FALSE;
						break;
					}
					if ($content === ',') {
						$pos--; // match in next run of for loop
						$commaSeparatedUses = TRUE;
						break;
					}
					$buffer[] = $content;
				};

				$useString = ltrim(implode('', $buffer), '\\');
				$parts = explode('\\', $useString);
				$as = array_pop($parts);
				list($use, $as) = preg_split('~\s+AS\s+~i', $useString) + [NULL, $as];
				$uses[$use] = $as;
			}
		}

		return $this->usings[$file] = $uses;
	}


	/**
	 * @param string $file
	 * @return string|NULL
	 */
	private function getNamespace(string $file)
	{
		if (isset($this->namespaces[$file])) {
			return $this->namespaces[$file];
		}

		$tokens = $this->getTokens($file);
		$max = count($tokens);
		$namespace = NULL;
		for ($pos = 0; $pos < $max; ++$pos) {
			list($type, $content) = is_array($tokens[$pos]) ? $tokens[$pos] : [NULL, $tokens[$pos]];

			if ($type === T_NAMESPACE) {
				$buffer = [];

				$pos++; // skip first whitespace

				while (TRUE) {
					$pos++;
					list($_, $content) = is_array($tokens[$pos]) ? $tokens[$pos] : [NULL, $tokens[$pos]];
					if ($content === ';') {
						break;
					}
					$buffer[] = $content;
				};

				$namespace = implode('', $buffer);
				break;
			}
		}

		return $this->namespaces[$file] = $namespace;
	}

}
