<?php

class InstructionsComposerTest extends TestCase {

    public function test_ability_to_mcp_tool_name() {
        $this->assertSame(
            'mcp-api-for-elementor-list-pages',
            McpApiForElementor\Instructions_Composer::ability_to_mcp_tool_name('mcp-api-for-elementor/list-pages')
        );
    }

    public function test_build_includes_runtime_catalog_when_abilities_registered() {
        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';
        McpApiForElementor\Abilities_Provider::register();

        $result = McpApiForElementor\Instructions_Composer::build();
        $this->assertStringContainsString('## Tool catalog', $result['markdown']);
        $this->assertStringContainsString('### ', $result['markdown']);
        $this->assertStringContainsString('| Name | Type | Required | Description |', $result['markdown']);
        $this->assertStringContainsString('mcp-api-for-elementor-list-pages', $result['markdown']);
        $this->assertStringNotContainsString('mcp-api-for-elementor-get-instructions', $result['markdown']);
    }
}
