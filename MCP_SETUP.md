# MCP Server Setup for CakePHP Boost

This guide shows you how to configure the CakePHP Boost MCP (Model Context Protocol) server with AI assistants like Claude Code.

## What is MCP?

The Model Context Protocol (MCP) allows AI assistants to access tools and data from your CakePHP application in real-time. The CakePHP Boost MCP server provides two powerful tools:

1. **search_documentation** - Search CakePHP documentation with natural language
2. **get_database_schema** - Get database schema information for any table

## Available Tools

### 1. search_documentation

Search CakePHP documentation using natural language queries.

**Parameters:**
- `query` (required): Search query (e.g., "how to save data", "belongsToMany association")
- `limit` (optional): Maximum number of results (default: 10)
- `category` (optional): Filter by category (orm, controller, validation, etc.)

**Example queries:**
- "How do I create a belongsToMany association?"
- "What's the correct way to validate email fields?"
- "How to use database transactions in CakePHP?"

### 2. get_database_schema

Get detailed schema information for database tables.

**Parameters:**
- `table` (optional): Specific table name (omit for all tables)
- `connection` (optional): Database connection name (default: "default")

**Returns:**
- Column definitions (type, length, null, default, auto_increment)
- Primary keys
- Indexes
- Foreign key constraints

## Configuration

### For Claude Code

Create or update `.claude/mcp_config.json` in your project root:

```json
{
  "mcpServers": {
    "cakephp-boost": {
      "command": "php",
      "args": [
        "bin/cake",
        "boost_mcp_server"
      ],
      "env": {
        "APP_NAME": "default"
      }
    }
  }
}
```

**Using absolute paths (recommended for global config):**

In `~/.claude/mcp_config.json`:

```json
{
  "mcpServers": {
    "cakephp-boost": {
      "command": "php",
      "args": [
        "/absolute/path/to/your/project/bin/cake",
        "boost_mcp_server"
      ],
      "cwd": "/absolute/path/to/your/project",
      "env": {
        "APP_NAME": "default"
      }
    }
  }
}
```

### For Cursor AI

Add to your Cursor settings (`.cursor/settings.json` or global settings):

```json
{
  "mcp.servers": {
    "cakephp-boost": {
      "command": "php",
      "args": ["bin/cake", "boost_mcp_server"]
    }
  }
}
```

### For Other MCP-Compatible Tools

The MCP server uses JSON-RPC 2.0 over stdio. Any tool supporting MCP can connect using:

```bash
php bin/cake boost_mcp_server
```

## Testing the MCP Server

Test the server manually:

```bash
# Start the server
php bin/cake boost_mcp_server

# In another terminal, send a test request
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}' | php bin/cake boost_mcp_server
```

## Usage Examples

Once configured, your AI assistant can use these tools automatically when you ask questions like:

**Documentation Search:**
- "How do I use CakePHP's ORM to save data?"
- "Show me examples of belongsToMany associations"
- "What's the validation syntax for email fields?"

**Schema Introspection:**
- "What columns does the users table have?"
- "Show me the foreign keys for the articles table"
- "What's the schema for all my database tables?"

The AI will automatically call the MCP server tools to fetch this information and provide accurate, context-aware answers.

## Troubleshooting

### Server won't start

- Check that PHP is in your PATH
- Verify bin/cake is executable: `chmod +x bin/cake`
- Test manually: `php bin/cake boost_mcp_server`

### Tools not appearing

- Restart your AI assistant after updating config
- Check JSON syntax in config file
- Verify the plugin is loaded: `bin/cake plugin list`

### Documentation search returns no results

- Index documentation first: `bin/cake boost_index --clear`
- Verify database exists: `ls -la tmp/boost/documentation.db`

### Schema tool fails

- Check database connection in `config/app_local.php`
- Verify tables exist: `bin/cake boost_schema`

## Security Notes

- The MCP server runs with your application's permissions
- Only use in development environments
- The server sanitizes foreign key information but be cautious with sensitive data
- Consider using a separate database connection for MCP if needed

## Advanced Configuration

### Custom Database Connection

Configure a read-only connection for MCP in `config/app.php`:

```php
'Datasources' => [
    'mcp' => [
        'className' => Connection::class,
        'driver' => Mysql::class,
        'host' => 'localhost',
        'username' => 'readonly_user',
        'password' => 'readonly_password',
        'database' => 'my_database',
    ],
],
```

Then use it via MCP:
```
AI: "Show me the schema for users table using the mcp connection"
```

### Limiting Documentation Results

Adjust default limits in MCP config:

The AI will automatically use reasonable limits, but you can hint:
```
AI: "Search documentation for 'validation' but only show 3 results"
```

## Further Reading

- [Model Context Protocol Specification](https://modelcontextprotocol.io/)
- [Claude Code MCP Documentation](https://docs.claude.com/claude-code)
- [CakePHP Boost GitHub](https://github.com/dereuromark/cakephp-boost)
