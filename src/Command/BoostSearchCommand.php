<?php
declare(strict_types=1);

namespace CakeBoost\Command;

use CakeBoost\Documentation\DocumentationIndexer;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Boost Search Command
 *
 * Search CakePHP documentation from the command line.
 */
class BoostSearchCommand extends Command {

	/**
	 * @inheritDoc
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser
			->setDescription('Search CakePHP documentation')
			->addArgument('query', [
				'help' => 'Search query',
				'required' => true,
			])
			->addOption('limit', [
				'short' => 'l',
				'help' => 'Maximum number of results',
				'default' => 10,
			])
			->addOption('type', [
				'short' => 't',
				'help' => 'Filter by type (api, book, guide, example)',
				'default' => null,
			])
			->addOption('category', [
				'short' => 'c',
				'help' => 'Filter by category (controller, model, helper, etc.)',
				'default' => null,
			]);

		return $parser;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$query = $args->getArgument('query');
		$limit = (int)$args->getOption('limit');
		$type = $args->getOption('type');
		$category = $args->getOption('category');

		$indexer = new DocumentationIndexer();

		$io->out('<info>Searching for:</info> ' . $query);
		$io->out('');

		$types = $type ? [$type] : [];
		$categories = $category ? [$category] : [];

		$results = $indexer->search($query, $limit, $types, $categories);

		if (empty($results)) {
			$io->warning('No results found.');

			return static::CODE_SUCCESS;
		}

		$io->out(sprintf('<success>Found %d results:</success>', count($results)));
		$io->out('');

		foreach ($results as $i => $result) {
			$io->out(sprintf(
				'<info>%d. %s</info> [%s]',
				$i + 1,
				$result['title'],
				$result['type'],
			));

			if (!empty($result['category'])) {
				$io->out('   Category: ' . $result['category']);
			}

			$io->out('   URL: <comment>' . $result['url'] . '</comment>');

			if (!empty($result['snippet'])) {
				// Clean HTML tags from snippet for console output
				$snippet = strip_tags($result['snippet']);
				$io->out('   ' . $snippet);
			}

			$io->out('');
		}

		return static::CODE_SUCCESS;
	}

}
