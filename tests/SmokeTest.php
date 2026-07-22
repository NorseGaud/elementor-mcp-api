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
