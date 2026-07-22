<?php

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase {

    public function test_plugin_version_constant_is_defined() {
        $this->assertTrue(defined('ELEMENTOR_MCP_API_VERSION'));
        $this->assertNotEmpty(ELEMENTOR_MCP_API_VERSION);
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+/',
            ELEMENTOR_MCP_API_VERSION
        );
    }

    public function test_core_classes_exist() {
        $this->assertTrue(class_exists('ElementorMcpApi\\Permissions'));
        $this->assertTrue(class_exists('ElementorMcpApi\\Element_Factory'));
        $this->assertTrue(class_exists('ElementorMcpApi\\Elementor_Data'));
        $this->assertTrue(class_exists('ElementorMcpApi\\REST_Controller'));
    }

    public function test_header_version_matches_constant() {
        $plugin_file = dirname(__DIR__) . '/elementor-mcp-api.php';
        $contents = file_get_contents($plugin_file);
        $this->assertNotFalse($contents);

        $this->assertSame(
            1,
            preg_match('/^\s*\*\s*Version:\s*(.+)$/mi', $contents, $header_matches)
        );
        $header_version = trim($header_matches[1]);

        $this->assertSame($header_version, ELEMENTOR_MCP_API_VERSION);
    }
}
