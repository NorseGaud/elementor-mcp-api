<?php
namespace McpApiForElementor;

/**
 * Registers a dedicated MCP Adapter server for this plugin.
 *
 * Endpoint: /wp-json/mcp-api-for-elementor/mcp
 * Server ID (STDIO): mcp-api-for-elementor
 */
class Mcp_Server {

    const SERVER_ID = 'mcp-api-for-elementor';
    const ROUTE_NAMESPACE = 'mcp-api-for-elementor';
    const ROUTE = 'mcp';

    /**
     * Hook: mcp_adapter_init
     *
     * @param object $adapter \WP\MCP\Core\McpAdapter instance.
     */
    public static function register($adapter): void {
        if (!did_action('elementor/loaded')) {
            return;
        }

        if (!class_exists(\WP\MCP\Transport\HttpTransport::class)) {
            return;
        }

        if (!class_exists(Abilities_Provider::class)) {
            require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-abilities-provider.php';
        }

        $error_handler = class_exists(\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class)
            ? \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class
            : null;

        $observability = class_exists(\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class)
            ? \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class
            : null;

        $result = $adapter->create_server(
            self::SERVER_ID,
            self::ROUTE_NAMESPACE,
            self::ROUTE,
            'MCP API for Elementor',
            'AI-driven Elementor page building tools.',
            'v' . MCP_API_FOR_ELEMENTOR_VERSION,
            [\WP\MCP\Transport\HttpTransport::class],
            $error_handler,
            $observability,
            Abilities_Provider::get_tool_ability_names(),
            [],
            []
        );

        if (is_wp_error($result) && defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('MCP API for Elementor: failed to create MCP server — ' . $result->get_error_message());
        }
    }
}
