<?php

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase {

    public function test_plugin_version_constant_is_defined() {
        $this->assertTrue(defined('MCP_API_FOR_ELEMENTOR_VERSION'));
        $this->assertNotEmpty(MCP_API_FOR_ELEMENTOR_VERSION);
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+/',
            MCP_API_FOR_ELEMENTOR_VERSION
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

        $result = McpApiForElementor\Instructions_Composer::build();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('markdown', $result);
        $this->assertArrayHasKey('plugin_version', $result);
        $this->assertSame(MCP_API_FOR_ELEMENTOR_VERSION, $result['plugin_version']);
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

        $this->assertSame($header_version, MCP_API_FOR_ELEMENTOR_VERSION);
    }
}
