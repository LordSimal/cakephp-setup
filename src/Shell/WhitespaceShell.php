<?php
namespace Setup\Shell;

use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Utility\Inflector;

/**
 * Shell to remove superfluous whitespace.
 *
 * @author Mark Scherer
 * @license MIT
 */
class WhitespaceShell extends Shell {

	/**
	 * Each report: [0] => found, [1] => corrected
	 *
	 * @var array
	 */
	public $report = [
		'leading' => [0, 0],
		'trailing' => [0, 0]
	];

	/**
	 * Whitespaces before or after <?php and ?>.
	 * The latter should be removed from PHP files by the way.
	 *
	 * @return void
	 */
	public function clean() {
		if (!empty($this->args[0])) {
			$folder = realpath($this->args[0]);
		} elseif ($this->params['plugin']) {
			$folder = Plugin::path(Inflector::classify($this->params['plugin']));
		} else {
			$folder = APP;
		}
		$App = new Folder($folder);
		$this->out('Checking *.php in ' . $folder);

		$files = $App->findRecursive('.*\.php');
		$this->out('Found ' . count($files) . ' files.');

		$action = $this->in('Continue? [y]/[n]', ['y', 'n'], 'n');
		if ($action !== 'y') {
			return $this->error('Aborted');
		}

		$folders = [];

		foreach ($files as $file) {
			$errors = [];
			$action = '';
			$this->out('Processing ' . $file, 1, Shell::VERBOSE);

			$c = file_get_contents($file);
			if (preg_match('/^[\n\r|\n|\r|\s]+\<\?php/', $c)) {
				$errors[] = 'leading';
			}
			if (preg_match('/\?\>[\n\r|\n|\r|\s]+$/', $c)) {
				$errors[] = 'trailing';
			}

			if (empty($errors)) {
				continue;
			}
			foreach ($errors as $e) {
				$this->report[$e][0]++;
			}
			$this->out('');
			$this->out('contains ' . implode(' and ', $errors) . ' whitespaces: ' . $this->shortPath($file));

			$dirname = dirname($file);
			if (in_array($dirname, $folders)) {
				$action = 'y';
			}

			while (empty($action)) {
				$action = $this->in('Remove? [y]/[n], [a] for all in this folder, [r] for all below, [*] for all files(!), [q] to quit', ['y', 'n', 'r', 'a', 'q', '*'], 'q');
			}

			if ($action === '*') {
				$action = 'y';

			} elseif ($action === 'a') {
				$action = 'y';
				$folders[] = $dirname;
				$this->out('All: ' . $dirname);
			}

			if ($action === 'q') {
				return $this->error('Abort... Done');
			}

			if ($action === 'y') {
				if (in_array('leading', $errors)) {
					$c = preg_replace('/^\s+\<\?php/', '<?php', $c);
				}
				if (in_array('trailing', $errors)) {
					$c = preg_replace('/\?\>\s+$/', '?>', $c);
				}

				file_put_contents($file, $c);
				foreach ($errors as $e) {
					$this->report[$e][1]++;
				}
				$this->out('fixed ' . implode(' and ', $errors) . ' whitespaces: ' . $this->shortPath($file));
			}
		}

		// Report.
		$this->out('--------');
		$this->out('found ' . $this->report['leading'][0] . ' leading, ' . $this->report['trailing'][0] . ' trailing ws');
		$this->out('fixed ' . $this->report['leading'][1] . ' leading, ' . $this->report['trailing'][1] . ' trailing ws');
	}

	/**
	 * Whitespaces at the end of the file
	 * If PHP file with trailing ?> that will be removed as per coding standards.
	 *
	 * @return void
	 */
	public function eof() {
		if (!empty($this->args[0])) {
			$folder = realpath($this->args[0]);
		} else {
			$folder = APP;
		}
		$App = new Folder($folder);
		$this->out('Checking *.php in ' . $folder);

		$files = $App->findRecursive('.*\.php');

		$this->out('Found ' . count($files) . ' files.');

		$action = $this->in('Continue? [y]/[n]', ['y', 'n'], 'n');
		if ($action !== 'y') {
			return $this->error('Aborted');
		}

		foreach ($files as $file) {
			$this->out('Processing ' . $file, 1, Shell::VERBOSE);
			$content = $store = file_get_contents($file);
			$content = trim($content);
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if ($ext === 'php' && substr($content, -2, 2) === '?>') {
				$content = substr($content, 0, -2);
			}

			$newline = PHP_EOL;
			$x = substr_count($content, "\r\n");
			if ($x > 0) {
				$newline = "\r\n";
			} else {
				$newline = "\n";
			}

			// add one new line at the end
			$content = trim($content) . $newline;
			if ($content !== $store) {
				file_put_contents($file, $content);
			}
		}
		$this->out('Done');
	}

	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				'ext' => [
					'short' => 'e',
					'help' => 'Specify extensions [php|txt|...]',
					'default' => '',
				],
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry run the clear command, no files will actually be deleted. Should be combined with verbose!',
					'boolean' => true
				],
				'plugin' => [
					'short' => 'p',
					'help' => 'Plugin',
					'default' => '',
				],
			]
		];

		return parent::getOptionParser()
			->description('The Whitespace Shell removes uncessary/wrong whitespaces.
Either provide a path as first argument, use -p PluginName or run it as it is for the complete APP dir.')
			->addSubcommand('clean', [
				'help' => 'Detect and remove any leading/trailing whitespaces',
				'parser' => $subcommandParser
			])
			->addSubcommand('eof', [
				'help' => 'Fix whitespace issues at the end of PHP files:
A single newline as per coding standards and no closing ?> tag for PHP files.',
				'parser' => $subcommandParser
			]);
	}

}
