<?php
declare(strict_types=1);

namespace CakeBoost\Command;

use CakeBoost\Documentation\DocumentationIndexer;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;

/**
 * MCP Server Command
 *
 * Implements Model Context Protocol (MCP) for AI assistants.
 * Provides tools for documentation search and schema introspection.
 */
class BoostMcpServerCommand extends Command {

	/**
	 * @var \Cake\Console\ConsoleIo
	 */
	protected ConsoleIo $io;

	/**
	 * @inheritDoc
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$this->io = $io;

		// MCP communication happens over stdio
		// Read JSON-RPC messages from stdin, write responses to stdout
		while (($line = fgets(STDIN)) !== false) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}

			$request = json_decode($line, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$this->sendError(null, -32700, 'Parse error');

				continue;
			}

			$response = $this->handleRequest($request);
			if ($response !== null) {
				$this->sendResponse($response);
			}
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * Handle MCP request
	 *
	 * @param array<string, mixed> $request JSON-RPC request
	 * @return array<string, mixed>|null Response
	 */
	protected function handleRequest(array $request): ?array {
		$method = $request['method'] ?? '';
		$params = $request['params'] ?? [];
		$id = $request['id'] ?? null;

		switch ($method) {
			case 'initialize':
				return $this->handleInitialize($id, $params);

			case 'tools/list':
				return $this->handleToolsList($id);

			case 'tools/call':
				return $this->handleToolsCall($id, $params);

			case 'ping':
				return ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['status' => 'ok']];

			default:
				return [
					'jsonrpc' => '2.0',
					'id' => $id,
					'error' => [
						'code' => -32601,
						'message' => 'Method not found',
					],
				];
		}
	}

	/**
	 * Handle initialize request
	 *
	 * @param mixed $id Request ID
	 * @param array<string, mixed> $params Parameters
	 * @return array<string, mixed> Response
	 */
	protected function handleInitialize($id, array $params): array {
		return [
			'jsonrpc' => '2.0',
			'id' => $id,
			'result' => [
				'protocolVersion' => '1.0',
				'serverInfo' => [
					'name' => 'cakephp-boost',
					'version' => '1.0.0',
				],
				'capabilities' => [
					'tools' => true,
				],
			],
		];
	}

	/**
	 * Handle tools/list request
	 *
	 * @param mixed $id Request ID
	 * @return array<string, mixed> Response
	 */
	protected function handleToolsList($id): array {
		return [
			'jsonrpc' => '2.0',
			'id' => $id,
			'result' => [
				'tools' => [
					[
						'name' => 'search_documentation',
						'description' => 'Search CakePHP documentation with natural language queries',
						'inputSchema' => [
							'type' => 'object',
							'properties' => [
								'query' => [
									'type' => 'string',
									'description' => 'Search query (e.g., "how to save data", "belongsToMany")',
								],
								'limit' => [
									'type' => 'integer',
									'description' => 'Maximum number of results (default: 10)',
									'default' => 10,
								],
								'category' => [
									'type' => 'string',
									'description' => 'Filter by category (orm, controller, validation, etc.)',
								],
							],
							'required' => ['query'],
						],
					],
					[
						'name' => 'get_database_schema',
						'description' => 'Get database schema information for tables',
						'inputSchema' => [
							'type' => 'object',
							'properties' => [
								'table' => [
									'type' => 'string',
									'description' => 'Specific table name (omit for all tables)',
								],
								'connection' => [
									'type' => 'string',
									'description' => 'Database connection name (default: "default")',
									'default' => 'default',
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Handle tools/call request
	 *
	 * @param mixed $id Request ID
	 * @param array<string, mixed> $params Parameters
	 * @return array<string, mixed> Response
	 */
	protected function handleToolsCall($id, array $params): array {
		$toolName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		try {
			$result = match ($toolName) {
				'search_documentation' => $this->toolSearchDocumentation($arguments),
				'get_database_schema' => $this->toolGetDatabaseSchema($arguments),
				default => throw new \RuntimeException('Unknown tool: ' . $toolName),
			};

			return [
				'jsonrpc' => '2.0',
				'id' => $id,
				'result' => [
					'content' => [
						[
							'type' => 'text',
							'text' => json_encode($result, JSON_PRETTY_PRINT),
						],
					],
				],
			];
		} catch (\Exception $e) {
			return [
				'jsonrpc' => '2.0',
				'id' => $id,
				'error' => [
					'code' => -32000,
					'message' => $e->getMessage(),
				],
			];
		}
	}

	/**
	 * Tool: Search Documentation
	 *
	 * @param array<string, mixed> $args Arguments
	 * @return array<string, mixed> Results
	 */
	protected function toolSearchDocumentation(array $args): array {
		$query = $args['query'] ?? '';
		$limit = (int)($args['limit'] ?? 10);
		$category = $args['category'] ?? null;

		if (empty($query)) {
			throw new \InvalidArgumentException('Query parameter is required');
		}

		$indexer = new DocumentationIndexer();

		$types = [];
		$categories = $category ? [$category] : [];

		$results = $indexer->search($query, $limit, $types, $categories);

		return [
			'query' => $query,
			'total_results' => count($results),
			'results' => $results,
		];
	}

	/**
	 * Tool: Get Database Schema
	 *
	 * @param array<string, mixed> $args Arguments
	 * @return array<string, mixed> Schema information
	 */
	protected function toolGetDatabaseSchema(array $args): array {
		$tableName = $args['table'] ?? null;
		$connectionName = $args['connection'] ?? 'default';

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get($connectionName);
		$schemaCollection = $connection->getSchemaCollection();

		$tables = $tableName ? [$tableName] : $schemaCollection->listTables();

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

		return [
			'connection' => $connectionName,
			'tables' => $result,
		];
	}

	/**
	 * Send JSON-RPC response
	 *
	 * @param array<string, mixed> $response Response data
	 * @return void
	 */
	protected function sendResponse(array $response): void {
		echo json_encode($response) . "\n";
		flush();
	}

	/**
	 * Send JSON-RPC error
	 *
	 * @param mixed $id Request ID
	 * @param int $code Error code
	 * @param string $message Error message
	 * @return void
	 */
	protected function sendError($id, int $code, string $message): void {
		$response = [
			'jsonrpc' => '2.0',
			'id' => $id,
			'error' => [
				'code' => $code,
				'message' => $message,
			],
		];

		$this->sendResponse($response);
	}

}
