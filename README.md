# CakePHP Boost Plugin

A development tool to enhance AI-assisted coding within CakePHP projects by providing essential context, structure, and introspection capabilities.

## Features

### Phase 1 - MVP (Current)

- **Documentation Search**: Fast, full-text search of CakePHP documentation using SQLite FTS5
- **Schema Introspection**: View database schema in human-readable format
- **Console Commands**: Simple CLI interface for indexing and searching documentation
- **Sample Documentation**: Pre-loaded with 20+ common CakePHP patterns and examples

## Installation

### For External Projects

```bash
composer require dereuromark/cakephp-boost --dev
```

Then add to your `config/plugins.php`:

```php
'Boost' => ['onlyCli' => true],
```

## Usage

### Indexing Documentation

Index CakePHP Book documentation:

```bash
# Index all documentation
bin/cake boost_index

# Clear existing index and re-index
bin/cake boost_index --clear

# Index only specific sources
bin/cake boost_index --source book
```

### Searching Documentation

Basic search:

```bash
bin/cake boost_search "belongsToMany"
```

Natural language queries:

```bash
bin/cake boost_search "how to save data"
bin/cake boost_search "how do I validate email fields"
bin/cake boost_search "what is the correct way to use transactions"
```

Filter by category:

```bash
bin/cake boost_search "validation" --category orm
bin/cake boost_search "controller" --category controller
```

Limit results:

```bash
bin/cake boost_search "query" --limit 5
```

### Viewing Database Schema

View all tables:

```bash
bin/cake boost_schema
```

View specific table with detailed information:

```bash
bin/cake boost_schema users
```

Output as JSON (useful for AI processing):

```bash
bin/cake boost_schema --format json
bin/cake boost_schema users --format json
```

Use different database connection:

```bash
bin/cake boost_schema --connection test
```

### Available Categories

- `orm` - Database, models, tables, associations
- `controller` - Controllers, components, request/response
- `view` - Templates, helpers, rendering
- `validation` - Form validation, rules
- `authentication` - User authentication
- `authorization` - Access control, permissions
- `testing` - PHPUnit tests, fixtures
- `middleware` - Middleware stack
- `routing` - Routes, prefixes
- `email` - Mailer, sending emails
- `caching` - Cache configuration and usage
- `events` - Event system
- `console` - CLI commands

## How It Works

1. **Indexing**: The `boost_index` command parses CakePHP documentation and stores it in a SQLite database with full-text search (FTS5) enabled
2. **Searching**: The `boost_search` command performs fast full-text queries against the indexed documentation
3. **Results**: Returns relevant documentation snippets with titles, URLs, and context

## Database

Documentation is stored in: `tmp/boost/documentation.db`

The database uses SQLite's FTS5 (Full-Text Search) with Porter stemming for intelligent word matching.

## Current Limitations (MVP)

- **Sample Data Only**: Currently uses pre-defined documentation snippets
- **No Real-Time Updates**: Documentation must be manually re-indexed
- **No API Docs**: Only CakePHP Book content is indexed (API docs coming in Phase 2)
- **Basic Search**: Keyword-based search only (semantic search coming in Phase 2)

## Roadmap

### Phase 2 - Semantic Search (Planned)

- Generate embeddings for documentation using OpenAI or local models
- Vector similarity search for context-aware results
- Better understanding of natural language queries

### Phase 3 - Guidelines & Integration (Planned)

- CakePHP 5.x coding guidelines
- Popular plugin guidelines (Tools, Shim, Authentication, etc.)
- Auto-generated context files for Claude Code

### Phase 4 - MCP Server (Optional)

- Model Context Protocol server for AI assistants
- Real-time application introspection
- Database schema access for AI

## Examples

### Find Association Documentation

```bash
$ bin/cake boost_search "belongsToMany"

Found 1 results:

1. Associations - BelongsToMany [book]
   Category: orm
   URL: https://book.cakephp.org/5/en/orm/associations.html#belongstomany
   The belongsToMany association is used when two models are associated through a join table...
```

### Find Validation Examples

```bash
$ bin/cake boost_search "validation"

Found 2 results:

1. Form Validation - Custom Validators [book]
   Category: validation
   URL: https://book.cakephp.org/5/en/core-libraries/validation.html#custom-validation-rules
   Create custom validation rules as methods in your Table class...

2. Validation Rules [book]
   Category: validation
   URL: https://book.cakephp.org/5/en/core-libraries/validation.html
   Add validation rules in your Table class validationDefault() method...
```

## Contributing

This is currently an internal plugin in the Sandbox project. Once extracted to a separate repository, contributions will be welcome!

## License

MIT License

## Credits

Inspired by [Laravel Boost](https://github.com/laravel/boost)
