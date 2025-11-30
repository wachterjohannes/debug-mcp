# Integration

This page explains how to integrate Symfony AI Mate with AI development tools.

## JetBrains AI Assistant

To connect Symfony AI Mate to JetBrains AI Assistant:

1. Press `Cmd` + `,` to open **Settings**.
2. Navigate to **Tools | AI Assistant | Model Context Protocol (MCP)**.
3. Click the **+** (Add) button.
4. Configure the server parameters:
   - **Name**: Symfony AI Mate
   - **Command type**: Select `stdio`.
   - **Executable**: `php`
   - **Arguments**: `/absolute/path/to/vendor/bin/mate serve`
5. Click **OK** to save.

**Note**: Replace `/absolute/path/to/` with the actual path to your project's vendor directory.

## Claude Desktop

To connect Symfony AI Mate to Claude Desktop:

1. Open Claude Desktop.
2. Go to **Settings** > **Developer** and click **Edit Config**.
   - Alternatively, open the file manually:
     - **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
     - **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
3. Add the server configuration to the `mcpServers` object in the JSON file:
   ```json
   {
     "mcpServers": {
       "symfony-ai-mate": {
         "command": "php",
         "args": ["/absolute/path/to/vendor/bin/mate", "serve"]
       }
     }
   }
   ```
4. Save the file and restart Claude Desktop.

**Note**: Replace `/absolute/path/to/` with the actual path to your project's vendor directory.

## Cursor

Configuration for Cursor coming soon.

## GitHub Copilot

Configuration for GitHub Copilot coming soon.

## Troubleshooting

### Server not starting

If the server doesn't start, check:
1. PHP version is 8.1 or higher: `php --version`
2. MCP server binary exists: `ls -la vendor/bin/mate`
3. Run server manually to see errors: `vendor/bin/mate serve`

### Tools not appearing

If AI tools don't see your MCP features:
1. Check that plugins are enabled in `.mcp.php`
2. Run discovery: `vendor/bin/mate discover`
3. Restart your AI assistant
4. Check logs for errors

### Permission issues

If you get permission errors:
```bash
chmod +x vendor/bin/mate
```
