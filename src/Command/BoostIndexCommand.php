<?php
declare(strict_types=1);

namespace CakeBoost\Command;

use CakeBoost\Documentation\DocumentationIndexer;
use CakeBoost\Documentation\Parser\BookParser;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Boost Index Command
 *
 * Index CakePHP documentation for searching.
 */
class BoostIndexCommand extends Command {

	/**
	 * @inheritDoc
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser
			->setDescription('Index CakePHP documentation for searching')
			->addOption('clear', [
				'short' => 'c',
				'help' => 'Clear existing index before indexing',
				'boolean' => true,
				'default' => false,
			])
			->addOption('source', [
				'short' => 's',
				'help' => 'Source to index (book, api, all)',
				'default' => 'all',
				'choices' => ['book', 'api', 'all'],
			]);

		return $parser;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$clear = $args->getOption('clear');
		$source = $args->getOption('source');

		$indexer = new DocumentationIndexer();

		if ($clear) {
			$io->out('<warning>Clearing existing index...</warning>');
			$indexer->clear();
		}

		$io->out('<info>Starting documentation indexing...</info>');
		$io->out('');

		$stats = ['indexed' => 0, 'errors' => 0];

		if ($source === 'book' || $source === 'all') {
			$io->out('Indexing CakePHP Book...');
			$bookStats = $this->indexBook($indexer, $io);
			$stats['indexed'] += $bookStats['indexed'];
			$stats['errors'] += $bookStats['errors'];
		}

		if ($source === 'api' || $source === 'all') {
			$io->out('Indexing CakePHP API...');
			$io->warning('API indexing not yet implemented');
		}

		$io->out('');
		$io->out(sprintf(
			'<success>Indexing complete!</success> Indexed: %d, Errors: %d',
			$stats['indexed'],
			$stats['errors'],
		));

		// Show current stats
		$dbStats = $indexer->getStats();
		$io->out('');
		$io->out('<info>Database Statistics:</info>');
		$io->out(sprintf('  Total documents: %d', $dbStats['total']));
		$io->out('  By type:');
		foreach ($dbStats['by_type'] as $type) {
			$io->out(sprintf('    - %s: %d', $type['type'], $type['count']));
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * Index CakePHP Book content
	 *
	 * @param \Boost\Documentation\DocumentationIndexer $indexer Indexer instance
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return array<string, int> Statistics
	 */
	protected function indexBook(DocumentationIndexer $indexer, ConsoleIo $io): array {
		$parser = new BookParser();

		try {
			$documents = $parser->parse();
			$indexed = 0;
			$errors = 0;

			foreach ($documents as $doc) {
				try {
					$indexer->addDocument(
						$doc['title'],
						$doc['content'],
						$doc['url'],
						'book',
						$doc['category'] ?? null,
						'cakephp-5.x',
					);
					$indexed++;

					if ($indexed % 10 === 0) {
						$io->verbose(sprintf('  Indexed %d documents...', $indexed));
					}
				} catch (\Exception $e) {
					$errors++;
					$io->verbose(sprintf('  Error indexing %s: %s', $doc['title'], $e->getMessage()));
				}
			}

			return ['indexed' => $indexed, 'errors' => $errors];
		} catch (\Exception $e) {
			$io->error('Failed to parse book: ' . $e->getMessage());

			return ['indexed' => 0, 'errors' => 1];
		}
	}

}
