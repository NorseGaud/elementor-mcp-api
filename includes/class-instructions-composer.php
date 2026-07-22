<?php
namespace McpApiForElementor;

/**
 * Builds the markdown document returned by get-instructions.
 *
 * Combines static guidance with a runtime catalog of registered MCP tools.
 */
class Instructions_Composer {

    const ABILITY_NAME = 'mcp-api-for-elementor/get-instructions';

    /**
     * @return array{markdown: string, plugin_version: string}
     */
    public static function build(): array {
        $version = defined('MCP_API_FOR_ELEMENTOR_VERSION')
            ? MCP_API_FOR_ELEMENTOR_VERSION
            : '';

        $parts = [];
        $parts[] = '# MCP API for Elementor — Instructions';
        $parts[] = '';
        $parts[] = 'Plugin version: `' . $version . '`';
        $parts[] = '';
        $parts[] = 'You are connected via MCP. Use the MCP tools from this server only (do not invent ad-hoc HTTP workflows).';
        $parts[] = 'You already called **get-instructions** — follow this document for all Elementor page work.';
        $parts[] = '';
        $parts[] = self::load_guidance();
        $parts[] = '';
        $parts[] = self::build_tool_catalog();
        $parts[] = '';
        $parts[] = '## Critical reminders';
        $parts[] = '';
        $parts[] = '- After any visual change, call `mcp-api-for-elementor-flush-css` (requires `manage_options`).';
        $parts[] = '- Never run write tools in parallel on the same page — each write loads, mutates, and saves the full tree. Sequential per page; cross-page parallelism is fine.';
        $parts[] = '- Prefer `mcp-api-for-elementor-get-page-structure` before full page data.';
        $parts[] = '';

        return [
            'markdown'       => implode("\n", $parts),
            'plugin_version' => $version,
        ];
    }

    public static function ability_to_mcp_tool_name(string $ability_name): string {
        return str_replace('/', '-', $ability_name);
    }

    private static function load_guidance(): string {
        $path = MCP_API_FOR_ELEMENTOR_PATH . 'includes/instructions-guidance.md';
        if (!is_readable($path)) {
            return "## Guidance\n\n_Static guidance file is missing from this install._";
        }
        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return "## Guidance\n\n_Static guidance file could not be read._";
        }
        return rtrim($contents);
    }

    private static function build_tool_catalog(): string {
        $lines = [];
        $lines[] = '## Tool catalog';
        $lines[] = '';
        $lines[] = 'Generated from the abilities registered by this plugin. Prefer these tools over guessing parameters.';
        $lines[] = '';

        if (!class_exists(Abilities_Provider::class)) {
            $provider = MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-abilities-provider.php';
            if (is_readable($provider)) {
                require_once $provider;
            }
        }

        if (!class_exists(Abilities_Provider::class)) {
            $lines[] = '_Tool catalog unavailable (abilities provider not loaded)._';
            return implode("\n", $lines);
        }

        $names = Abilities_Provider::get_tool_ability_names();
        $has_wp_get = function_exists('wp_get_ability');

        if (!$has_wp_get) {
            $lines[] = '_Runtime ability metadata is unavailable in this context. Tools (MCP names):_';
            $lines[] = '';
            foreach ($names as $name) {
                if ($name === self::ABILITY_NAME) {
                    continue;
                }
                $lines[] = '- `' . self::ability_to_mcp_tool_name($name) . '`';
            }
            return implode("\n", $lines);
        }

        foreach ($names as $name) {
            if ($name === self::ABILITY_NAME) {
                continue;
            }

            $ability = wp_get_ability($name);
            if (!$ability || !is_object($ability)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('MCP API for Elementor: ability not found for catalog: ' . $name);
                }
                continue;
            }

            $label = method_exists($ability, 'get_label') ? (string) $ability->get_label() : $name;
            $description = method_exists($ability, 'get_description') ? (string) $ability->get_description() : '';
            $schema = method_exists($ability, 'get_input_schema') ? $ability->get_input_schema() : [];
            if (!is_array($schema)) {
                $schema = [];
            }

            $lines[] = '### ' . $label;
            $lines[] = '';
            $lines[] = '- **Tool:** `' . self::ability_to_mcp_tool_name($name) . '`';
            $lines[] = '- **Ability:** `' . $name . '`';
            $lines[] = '';
            if ($description !== '') {
                $lines[] = $description;
                $lines[] = '';
            }

            $properties = isset($schema['properties']) && is_array($schema['properties'])
                ? $schema['properties']
                : [];
            $required = isset($schema['required']) && is_array($schema['required'])
                ? $schema['required']
                : [];

            if ($properties === []) {
                $lines[] = '_No input parameters._';
                $lines[] = '';
                continue;
            }

            $lines[] = '| Name | Type | Required | Description |';
            $lines[] = '|------|------|----------|-------------|';
            foreach ($properties as $prop_name => $prop) {
                if (!is_array($prop)) {
                    $prop = [];
                }
                $type = self::format_schema_type($prop['type'] ?? '');
                $is_required = in_array($prop_name, $required, true) ? 'yes' : 'no';
                $prop_desc = isset($prop['description']) ? (string) $prop['description'] : '';
                $prop_desc = str_replace('|', '\\|', $prop_desc);
                $lines[] = '| `' . $prop_name . '` | ' . $type . ' | ' . $is_required . ' | ' . $prop_desc . ' |';
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param mixed $type
     */
    private static function format_schema_type($type): string {
        if (is_array($type)) {
            $parts = array_map('strval', $type);
            return implode('|', $parts);
        }
        if ($type === '' || $type === null) {
            return 'any';
        }
        return (string) $type;
    }
}
