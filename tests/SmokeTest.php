<?php

class SmokeTest extends TestCase {

    public function test_plugin_version_constant_is_defined() {
        $this->assertTrue(defined('MCPAPFOE_VERSION'));
        $this->assertNotEmpty(MCPAPFOE_VERSION);
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+/',
            MCPAPFOE_VERSION
        );
    }

    public function test_core_classes_exist() {
        $this->assertTrue(class_exists('McpApiForElementor\\Permissions'));
        $this->assertTrue(class_exists('McpApiForElementor\\Element_Factory'));
        $this->assertTrue(class_exists('McpApiForElementor\\Elementor_Data'));
        $this->assertTrue(class_exists('McpApiForElementor\\REST_Controller'));
        $this->assertTrue(class_exists('McpApiForElementor\\Instructions_Composer'));
    }

    public function test_instructions_composer_builds_markdown() {
        $guidance = dirname(__DIR__) . '/includes/instructions-guidance.md';
        $this->assertFileExists($guidance);

        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';
        McpApiForElementor\Abilities_Provider::register();

        $result = McpApiForElementor\Instructions_Composer::build();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('markdown', $result);
        $this->assertArrayHasKey('plugin_version', $result);
        $this->assertSame(MCPAPFOE_VERSION, $result['plugin_version']);
        $this->assertStringContainsString('## Workflow', $result['markdown']);
        $this->assertStringContainsString('mcp-api-for-elementor-list-pages', $result['markdown']);
        $this->assertStringNotContainsString('curl -', $result['markdown']);
        $guidance_body = file_get_contents($guidance);
        $this->assertIsString($guidance_body);
        $this->assertStringNotContainsString('curl', $guidance_body);
    }

    public function test_header_version_matches_constant() {
        $plugin_file = dirname(__DIR__) . '/mcp-api-for-elementor.php';
        $contents = file_get_contents($plugin_file);
        $this->assertNotFalse($contents);

        $this->assertSame(
            1,
            preg_match('/^\s*\*\s*Version:\s*(.+)$/mi', $contents, $header_matches)
        );
        $header_version = trim($header_matches[1]);

        $this->assertSame($header_version, MCPAPFOE_VERSION);
    }

    public function test_page_settings_abilities_are_listed() {
        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';
        $names = McpApiForElementor\Abilities_Provider::get_tool_ability_names();
        $this->assertContains('mcp-api-for-elementor/get-page-settings', $names);
        $this->assertContains('mcp-api-for-elementor/update-page-settings', $names);
        $this->assertCount(24, $names);
    }

    public function test_merge_assoc_settings_deep_merges_globals() {
        $current = [
            'background_background' => 'classic',
            'background_color' => '#FFFFFF',
            '__globals__' => [
                'background_color' => '',
                'keep_me' => 'yes',
            ],
        ];
        $incoming = [
            'background_background' => 'gradient',
            'background_color' => '#75091E0D',
            '__globals__' => [
                'background_color_b' => 'globals/colors?id=176f4bf',
            ],
        ];
        $merged = McpApiForElementor\Elementor_Data::merge_assoc_settings($current, $incoming);
        $this->assertSame('gradient', $merged['background_background']);
        $this->assertSame('#75091E0D', $merged['background_color']);
        $this->assertSame('', $merged['__globals__']['background_color']);
        $this->assertSame('yes', $merged['__globals__']['keep_me']);
        $this->assertSame(
            'globals/colors?id=176f4bf',
            $merged['__globals__']['background_color_b']
        );
    }

    public function test_instructions_mention_page_settings_tools() {
        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';
        McpApiForElementor\Abilities_Provider::register();

        $result = McpApiForElementor\Instructions_Composer::build();
        $this->assertStringContainsString(
            'mcp-api-for-elementor-get-page-settings',
            $result['markdown']
        );
        $this->assertStringContainsString(
            'mcp-api-for-elementor-update-page-settings',
            $result['markdown']
        );
    }
}
