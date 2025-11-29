# Integration

This page explains how integrate an MCP server with AI tools.

## Jetbrains AI

To connect an external MCP server to JetBrains AI Assistant:

1.  Press `Cmd` + `,` to open **Settings**.
2.  Navigate to **Tools | AI Assistant | Model Context Protocol (MCP)**.
3.  Click the **+** (Add) button.
4.  Configure the server parameters:
    *   **Name**: Enter a descriptive name.
    *   **Command type**: Select `stdio`.
    *   **Executable**: `php`
    *   **Arguments**: `vendor/bin/debug-mcp`
5.  Click **OK** to save.

## Claude Desktop

To connect an external MCP server to Claude Desktop:

1.  Open Claude Desktop.
2.  Go to **Settings** > **Developer** and click **Edit Config**.
    *   Alternatively, open the file manually:
        *   **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
        *   **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
3.  Add the server configuration to the `mcpServers` object in the JSON file:
    ```json
    {
      "mcpServers": {
        "debug-mcp": {
          "command": "php",
          "args": ["/full/path/to/vendor/bin/debug-mcp"]
        }
      }
    }
    ```
4.  Save the file and restart Claude Desktop.


## Cursor

