<?php

namespace Setup\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Routing\Router;

/**
 * Test CLI for Router and alike.
 *
 * @author Mark Scherer
 * @license MIT
 */
class TestCliShell extends Shell {

	/**
	 * Test that urls are generated properly.
	 *
	 * @return void
	 */
	public function router() {
		$url = Router::url('/');
		$this->out('Router::url(\'/\'): ' . PHP_EOL . "\t" . $url);

		$arrayUrl = ['controller' => 'Test'];
		if ($this->param('prefix')) {
			/** @var string $prefix */
			$prefix = $this->param('prefix');
			$arrayUrl['prefix'] = $prefix;
		}
		if ($this->param('plugin')) {
			/** @var string $plugin */
			$plugin = $this->param('plugin');
			$arrayUrl['plugin'] = $plugin;
		}

		$url = Router::url($arrayUrl);
		$text = $this->_urlToText($arrayUrl);

		$this->out('Router::url([' . $text . ']): ' . PHP_EOL . "\t" . $url);

		$url = Router::url('/', true);
		$this->out('Router::url(\'/\', true): ' . PHP_EOL . "\t" . $url);

		$url = Router::url($arrayUrl, true);

		$this->out('Router::url([' . $text . '], true): ' . PHP_EOL . "\t" . $url);
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = [
			'options' => [
				'plugin' => [
					'short' => 'p',
					'help' => 'Plugin (optional)',
					'default' => '',
				],
				'prefix' => [
					'short' => 'x',
					'help' => 'Prefix (optional)',
					'default' => '',
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('Test CLI')
			->addSubcommand('router', [
				'help' => 'Test router environment',
				'parser' => $parser,
			]);
	}

	/**
	 * @param array<string, string> $arrayUrl
	 * @return string
	 */
	protected function _urlToText(array $arrayUrl) {
		$url = [];
		foreach ($arrayUrl as $k => $v) {
			$url[] = "'$k' => '$v'";
		}

		return implode(', ', $url);
	}

}
