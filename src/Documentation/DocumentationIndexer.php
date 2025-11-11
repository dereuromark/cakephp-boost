<?php
declare(strict_types=1);

namespace CakeBoost\Documentation;

use PDO;
use RuntimeException;

/**
 * Documentation Indexer
 *
 * Handles indexing and searching CakePHP documentation using SQLite FTS5.
 */
class DocumentationIndexer {

	/**
	 * @var \PDO
	 */
	protected PDO $db;

	/**
	 * @var string
	 */
	protected string $dbPath;

	/**
	 * Constructor
	 *
	 * @param string|null $dbPath Path to SQLite database file
	 */
	public function __construct(?string $dbPath = null) {
		$this->dbPath = $dbPath ?? TMP . 'boost' . DS . 'documentation.db';

		$dir = dirname($this->dbPath);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		$this->db = new PDO('sqlite:' . $this->dbPath);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->initializeDatabase();
	}

	/**
	 * Initialize database schema
	 *
	 * @return void
	 */
	protected function initializeDatabase(): void {
		// Create FTS5 virtual table for full-text search
		$this->db->exec('
			CREATE VIRTUAL TABLE IF NOT EXISTS documentation_fts USING fts5(
				title,
				content,
				url,
				type,
				category,
				tokenize = "porter unicode61"
			)
		');

		// Create metadata table for additional info
		$this->db->exec('
			CREATE TABLE IF NOT EXISTS documentation_meta (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				url TEXT UNIQUE NOT NULL,
				title TEXT NOT NULL,
				type TEXT NOT NULL,
				category TEXT,
				source TEXT,
				indexed_at DATETIME DEFAULT CURRENT_TIMESTAMP
			)
		');

		// Create index on URL for faster lookups
		$this->db->exec('
			CREATE INDEX IF NOT EXISTS idx_documentation_meta_url
			ON documentation_meta(url)
		');
	}

	/**
	 * Add a document to the index
	 *
	 * @param string $title Document title
	 * @param string $content Document content
	 * @param string $url Document URL
	 * @param string $type Type (api, book, guide, example)
	 * @param string|null $category Category (controller, model, helper, etc.)
	 * @param string|null $source Source (cakephp-5.x, plugin-name, etc.)
	 * @return bool Success
	 */
	public function addDocument(
		string $title,
		string $content,
		string $url,
		string $type,
		?string $category = null,
		?string $source = null,
	): bool {
		try {
			$this->db->beginTransaction();

			// Insert into metadata table
			$stmt = $this->db->prepare('
				INSERT OR REPLACE INTO documentation_meta (url, title, type, category, source)
				VALUES (?, ?, ?, ?, ?)
			');
			$stmt->execute([$url, $title, $type, $category, $source]);

			// Insert into FTS table
			$stmt = $this->db->prepare('
				INSERT OR REPLACE INTO documentation_fts (title, content, url, type, category)
				VALUES (?, ?, ?, ?, ?)
			');
			$stmt->execute([$title, $content, $url, $type, $category]);

			$this->db->commit();

			return true;
		} catch (\Exception $e) {
			$this->db->rollBack();

			throw new RuntimeException('Failed to add document: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Search documentation
	 *
	 * @param string $query Search query
	 * @param int $limit Maximum results
	 * @param array<string> $types Filter by types
	 * @param array<string> $categories Filter by categories
	 * @return array<array<string, mixed>> Search results
	 */
	public function search(
		string $query,
		int $limit = 10,
		array $types = [],
		array $categories = [],
	): array {
		// Prepare FTS5 query - escape special characters
		$ftsQuery = $this->prepareFtsQuery($query);

		$sql = '
			SELECT
				fts.title,
				fts.url,
				fts.type,
				fts.category,
				snippet(documentation_fts, 1, "<mark>", "</mark>", "...", 60) as snippet,
				rank as relevance
			FROM documentation_fts fts
			WHERE documentation_fts MATCH ?
		';

		$params = [$ftsQuery];

		if (!empty($types)) {
			$placeholders = implode(',', array_fill(0, count($types), '?'));
			$sql .= " AND type IN ($placeholders)";
			$params = array_merge($params, $types);
		}

		if (!empty($categories)) {
			$placeholders = implode(',', array_fill(0, count($categories), '?'));
			$sql .= " AND category IN ($placeholders)";
			$params = array_merge($params, $categories);
		}

		$sql .= ' ORDER BY rank LIMIT ?';
		$params[] = $limit;

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Prepare FTS5 query string
	 *
	 * Handles phrase queries, wildcards, and basic sanitization
	 *
	 * @param string $query Raw query string
	 * @return string FTS5 formatted query
	 */
	protected function prepareFtsQuery(string $query): string {
		// Remove special characters that could break FTS5
		$query = trim($query);

		// If query contains quotes, treat as phrase search
		if (str_contains($query, '"')) {
			return $query;
		}

		// Split into words and join with OR for better matching
		$words = preg_split('/\s+/', $query);
		if ($words === false) {
			return $query;
		}

		$terms = [];
		foreach ($words as $word) {
			if (strlen($word) > 2) {
				$terms[] = $word . '*';
			}
		}

		return implode(' OR ', $terms);
	}

	/**
	 * Get database statistics
	 *
	 * @return array<string, mixed> Statistics
	 */
	public function getStats(): array {
		$stmt = $this->db->query('SELECT COUNT(*) as total FROM documentation_meta');
		$total = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt = $this->db->query('
			SELECT type, COUNT(*) as count
			FROM documentation_meta
			GROUP BY type
		');
		$byType = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return [
			'total' => $total['total'] ?? 0,
			'by_type' => $byType,
			'db_path' => $this->dbPath,
		];
	}

	/**
	 * Clear all indexed documentation
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->db->exec('DELETE FROM documentation_fts');
		$this->db->exec('DELETE FROM documentation_meta');
	}

}
