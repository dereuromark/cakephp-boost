<?php
declare(strict_types=1);

namespace CakeBoost\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Boost Schema Command
 *
 * Display database schema information in a readable format.
 */
class BoostSchemaCommand extends Command {

	/**
	 * @inheritDoc
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser
			->setDescription('Display database schema information')
			->addArgument('table', [
				'help' => 'Specific table name to show (optional)',
				'required' => false,
			])
			->addOption('connection', [
				'short' => 'c',
				'help' => 'Database connection to use',
				'default' => 'default',
			])
			->addOption('format', [
				'short' => 'f',
				'help' => 'Output format (table, json)',
				'default' => 'table',
				'choices' => ['table', 'json'],
			]);

		return $parser;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$connectionName = $args->getOption('connection');
		$tableName = $args->getArgument('table');
		$format = $args->getOption('format');

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get($connectionName);
		$schemaCollection = $connection->getSchemaCollection();

		$tables = $tableName ? [$tableName] : $schemaCollection->listTables();

		if ($format === 'json') {
			return $this->outputJson($tables, $schemaCollection, $io);
		}

		return $this->outputTable($tables, $schemaCollection, $io, $tableName !== null);
	}

	/**
	 * Output schema as formatted tables
	 *
	 * @param array<string> $tables Table names
	 * @param \Cake\Database\Schema\CollectionInterface $schemaCollection Schema collection
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @param bool $detailed Show detailed info
	 * @return int Exit code
	 */
	protected function outputTable(array $tables, $schemaCollection, ConsoleIo $io, bool $detailed): int {
		if (empty($tables)) {
			$io->warning('No tables found in database');

			return static::CODE_SUCCESS;
		}

		foreach ($tables as $table) {
			$schema = $schemaCollection->describe($table);

			$io->out('');
			$io->out('<info>Table: ' . $table . '</info>');
			$io->out(str_repeat('=', 80));

			$columns = $schema->columns();

			if ($detailed) {
				// Detailed view for single table
				$io->out('');
				$io->out('<comment>Columns:</comment>');

				foreach ($columns as $column) {
					$columnSchema = $schema->getColumn($column);
					$io->out('');
					$io->out('  <info>' . $column . '</info>');
					$io->out('    Type: ' . $columnSchema['type']);
					$io->out('    Length: ' . ($columnSchema['length'] ?? 'N/A'));
					$io->out('    Null: ' . ($columnSchema['null'] ? 'YES' : 'NO'));
					$io->out('    Default: ' . ($columnSchema['default'] ?? 'NULL'));

					if (!empty($columnSchema['autoIncrement'])) {
						$io->out('    Auto Increment: YES');
					}
				}

				// Primary key
				$primaryKey = $schema->getPrimaryKey();
				if (!empty($primaryKey)) {
					$io->out('');
					$io->out('<comment>Primary Key:</comment>');
					$io->out('  ' . implode(', ', $primaryKey));
				}

				// Indexes
				$indexes = $schema->indexes();
				if (!empty($indexes)) {
					$io->out('');
					$io->out('<comment>Indexes:</comment>');
					foreach ($indexes as $index) {
						$indexData = $schema->getIndex($index);
						$io->out('  ' . $index . ': ' . implode(', ', $indexData['columns']));
					}
				}

				// Foreign keys
				$constraints = $schema->constraints();
				if (!empty($constraints)) {
					$io->out('');
					$io->out('<comment>Foreign Keys:</comment>');
					foreach ($constraints as $constraint) {
						$constraintData = $schema->getConstraint($constraint);
						if ($constraintData['type'] === 'foreign') {
							$io->out(sprintf(
								'  %s: %s -> %s(%s)',
								$constraint,
								implode(', ', $constraintData['columns']),
								$constraintData['references'][0],
								implode(', ', $constraintData['references'][1]),
							));
						}
					}
				}
			} else {
				// Summary view for multiple tables
				$io->out('Columns: ' . count($columns));

				$primaryKey = $schema->getPrimaryKey();
				if (!empty($primaryKey)) {
					$io->out('Primary Key: ' . implode(', ', $primaryKey));
				}

				$columnList = [];
				foreach ($columns as $column) {
					$columnSchema = $schema->getColumn($column);
					$columnList[] = $column . ' (' . $columnSchema['type'] . ')';
				}
				$io->out('Fields: ' . implode(', ', $columnList));
			}
		}

		$io->out('');
		$io->out('<success>Total tables: ' . count($tables) . '</success>');

		return static::CODE_SUCCESS;
	}

	/**
	 * Output schema as JSON
	 *
	 * @param array<string> $tables Table names
	 * @param \Cake\Database\Schema\CollectionInterface $schemaCollection Schema collection
	 * @param \Cake\Console\ConsoleIo $io Console IO
	 * @return int Exit code
	 */
	protected function outputJson(array $tables, $schemaCollection, ConsoleIo $io): int {
		$result = [];

		foreach ($tables as $table) {
			$schema = $schemaCollection->describe($table);

			$columns = [];
			foreach ($schema->columns() as $column) {
				$columns[$column] = $schema->getColumn($column);
			}

			$indexes = [];
			foreach ($schema->indexes() as $index) {
				$indexes[$index] = $schema->getIndex($index);
			}

			$constraints = [];
			foreach ($schema->constraints() as $constraint) {
				$constraints[$constraint] = $schema->getConstraint($constraint);
			}

			$result[$table] = [
				'columns' => $columns,
				'primaryKey' => $schema->getPrimaryKey(),
				'indexes' => $indexes,
				'constraints' => $constraints,
			];
		}

		$io->out(json_encode($result, JSON_PRETTY_PRINT));

		return static::CODE_SUCCESS;
	}

}
