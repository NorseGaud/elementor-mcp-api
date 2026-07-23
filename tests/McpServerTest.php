<?php

class McpServerTest extends TestCase {

    public function test_register_requires_elementor_loaded() {
        require_once dirname(__DIR__) . '/includes/class-mcp-server.php';

        $calls = [];
        $adapter = new class($calls) {
            public array $calls;
            public function __construct(&$calls) {
                $this->calls = &$calls;
            }
            public function create_server(...$args) {
                $this->calls[] = $args;
                return true;
            }
        };

        McpApiForElementor\Mcp_Server::register($adapter);
        $this->assertSame([], $calls);

        $GLOBALS['mcp_test_state']['actions']['_did']['elementor/loaded'] = 1;
        McpApiForElementor\Mcp_Server::register($adapter);
        $this->assertCount(1, $calls);
        $this->assertSame(McpApiForElementor\Mcp_Server::SERVER_ID, $calls[0][0]);
        $this->assertSame(McpApiForElementor\Mcp_Server::ROUTE_NAMESPACE, $calls[0][1]);
        $this->assertSame(McpApiForElementor\Mcp_Server::ROUTE, $calls[0][2]);
        $this->assertSame(24, count($calls[0][9]));
    }

    public function test_register_handles_wp_error_result() {
        require_once dirname(__DIR__) . '/includes/class-mcp-server.php';
        $GLOBALS['mcp_test_state']['actions']['_did']['elementor/loaded'] = 1;

        $adapter = new class {
            public function create_server(...$args) {
                return new WP_Error('fail', 'boom');
            }
        };

        McpApiForElementor\Mcp_Server::register($adapter);
        $this->assertTrue(true); // exercised error branch without fatal
    }
}
