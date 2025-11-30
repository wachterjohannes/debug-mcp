#!/bin/bash

echo "=== Testing Individual Tools ==="
echo ""

# Test php-version tool
echo "1. Testing php-version tool:"
(
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
sleep 0.3
echo '{"jsonrpc":"2.0","method":"notifications/initialized"}'
sleep 0.3
echo '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"php-version","arguments":{}}}'
sleep 0.5
) | timeout 3 php bin/mate.php serve 2>&1 | grep -A 10 '"content"' | head -15

echo ""
echo "2. Testing operating-system tool:"
(
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
sleep 0.3
echo '{"jsonrpc":"2.0","method":"notifications/initialized"}'
sleep 0.3
echo '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"operating-system","arguments":{}}}'
sleep 0.5
) | timeout 3 php bin/mate.php serve 2>&1 | grep -A 10 '"content"' | head -15

echo ""
echo "3. Testing php-extensions tool:"
(
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
sleep 0.3
echo '{"jsonrpc":"2.0","method":"notifications/initialized"}'
sleep 0.3
echo '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"php-extensions","arguments":{}}}'
sleep 0.5
) | timeout 3 php bin/mate.php serve 2>&1 | grep -A 5 '"content"' | head -10
