#!/bin/bash
echo "=== MCP Server Initialize Response ==="
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test-client","version":"1.0.0"}}}' | php bin/mate.php serve 2>&1 | grep -E '^\{' | jq .

echo ""
echo "=== Available MCP Tools ==="
(
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test-client","version":"1.0.0"}}}'
sleep 0.5
echo '{"jsonrpc":"2.0","method":"notifications/initialized"}'
sleep 0.5
echo '{"jsonrpc":"2.0","id":2,"method":"tools/list"}'
sleep 0.5
) | timeout 3 php bin/mate.php serve 2>&1 | grep -E '^\{' | tail -1 | jq '.result.tools[] | {name, description}'

echo ""
echo "=== Sample Tool Execution: php-version ==="
(
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test-client","version":"1.0.0"}}}'
sleep 0.5
echo '{"jsonrpc":"2.0","method":"notifications/initialized"}'
sleep 0.5
echo '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"php-version","arguments":{}}}'
sleep 0.5
) | timeout 3 php bin/mate.php serve 2>&1 | grep -E '^\{' | tail -1 | jq '.result'
