<?php

class AbilitiesProviderTest extends TestCase {

    /** @var array<string, array> */
    private array $abilities = [];

    protected function setUp(): void {
        parent::setUp();
        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';
        Elementor\Plugin::$instance->widgets_manager->widgets['heading'] = new Elementor\Widget_Base(
            'heading',
            'Heading',
            ['title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Hi']]
        );
        McpApiForElementor\Abilities_Provider::register_category();
        McpApiForElementor\Abilities_Provider::register();
        $this->abilities = $GLOBALS['mcp_test_state']['abilities'];
    }

    private function execute(string $name, array $input = []) {
        $this->assertArrayHasKey($name, $this->abilities);
        $cb = $this->abilities[$name]['execute_callback'];
        return $cb($input);
    }

    private function permitted(string $name, $input = null): bool {
        $cb = $this->abilities[$name]['permission_callback'];
        return (bool) $cb($input);
    }

    public function test_registers_all_named_abilities() {
        $names = McpApiForElementor\Abilities_Provider::get_tool_ability_names();
        $this->assertCount(24, $names);
        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $this->abilities);
            $this->assertArrayHasKey('permission_callback', $this->abilities[$name]);
            $this->assertArrayHasKey('execute_callback', $this->abilities[$name]);
        }
        $this->assertArrayHasKey('mcp-api-for-elementor', $GLOBALS['mcp_test_state']['ability_categories']);
    }

    /**
     * Cursor's MCP UI validator rejects tools whose outputSchema.type is "array".
     * Top-level output schemas must be objects (array results wrapped as { items: [...] }).
     */
    public function test_all_output_schemas_are_objects() {
        foreach ($this->abilities as $name => $ability) {
            if (!isset($ability['output_schema'])) {
                continue;
            }
            $this->assertSame(
                'object',
                $ability['output_schema']['type'] ?? null,
                "{$name} output_schema.type must be \"object\" for Cursor MCP catalog validation"
            );
        }
    }

    public function test_list_abilities_return_items_object() {
        $this->insert_page('List Wrap Page');

        $pages = $this->execute('mcp-api-for-elementor/list-pages');
        $this->assertIsArray($pages);
        $this->assertArrayHasKey('items', $pages);
        $this->assertNotEmpty($pages['items']);
        $this->assertArrayHasKey('id', $pages['items'][0]);

        $templates = $this->execute('mcp-api-for-elementor/list-templates');
        $this->assertArrayHasKey('items', $templates);
        $this->assertIsArray($templates['items']);

        $widgets = $this->execute('mcp-api-for-elementor/list-widgets');
        $this->assertArrayHasKey('items', $widgets);
        $this->assertIsArray($widgets['items']);
    }

    public function test_permission_helpers() {
        $this->grant_caps(['edit_pages']);
        $this->assertTrue(McpApiForElementor\Abilities_Provider::can_read());
        $this->assertTrue(McpApiForElementor\Abilities_Provider::can_edit());
        $this->assertFalse(McpApiForElementor\Abilities_Provider::can_manage());
        $this->assertTrue($this->permitted('mcp-api-for-elementor/build-page', ['data' => []]));
        $this->assertFalse($this->permitted('mcp-api-for-elementor/build-page', [
            'data' => [],
            'images' => [['source_path' => 'x.png']],
        ]));
    }

    public function test_execute_core_abilities() {
        $page_id = $this->insert_page('Ability Page', $this->sample_tree());
        $kit_id = wp_insert_post(['post_title' => 'Kit', 'post_type' => 'elementor_library', 'post_status' => 'publish']);
        update_option('elementor_active_kit', $kit_id);
        update_post_meta($kit_id, '_elementor_page_settings', []);

        $instructions = $this->execute('mcp-api-for-elementor/get-instructions');
        $this->assertArrayHasKey('markdown', $instructions);

        $pages = $this->execute('mcp-api-for-elementor/list-pages');
        $this->assertNotEmpty($pages['items']);

        $structure = $this->execute('mcp-api-for-elementor/get-page-structure', ['post_id' => $page_id]);
        $this->assertSame($page_id, $structure['id']);
        $this->assertArrayHasKey('structure', $structure);

        $data = $this->execute('mcp-api-for-elementor/get-page-data', ['post_id' => $page_id]);
        $this->assertArrayHasKey('data', $data);

        $saved = $this->execute('mcp-api-for-elementor/save-page-data', [
            'post_id' => $page_id,
            'data' => $this->sample_tree(),
        ]);
        $this->assertTrue($saved['success']);

        $created = $this->execute('mcp-api-for-elementor/create-page', [
            'title' => 'From Ability',
            'status' => 'draft',
            'data' => $this->sample_tree(),
        ]);
        $this->assertArrayHasKey('post_id', $created);

        $meta = $this->execute('mcp-api-for-elementor/update-page-meta', [
            'post_id' => $page_id,
            'title' => 'Ability Meta',
        ]);
        $this->assertSame('Ability Meta', $meta['title']);

        $settings = $this->execute('mcp-api-for-elementor/get-page-settings', ['post_id' => $page_id]);
        $this->assertArrayHasKey('settings', $settings);

        $updated_settings = $this->execute('mcp-api-for-elementor/update-page-settings', [
            'post_id' => $page_id,
            'settings' => ['background_color' => '#111'],
        ]);
        $this->assertSame('#111', $updated_settings['settings']['background_color']);

        $element = $this->execute('mcp-api-for-elementor/get-element', [
            'post_id' => $page_id,
            'element_id' => 'head0001',
        ]);
        $this->assertSame('heading', $element['widgetType']);

        $added = $this->execute('mcp-api-for-elementor/add-element', [
            'post_id' => $page_id,
            'parent_id' => 'col00001',
            'element' => [
                'elType' => 'widget',
                'widgetType' => 'button',
                'settings' => ['text' => 'Go'],
            ],
        ]);
        $this->assertTrue($added['success']);
        $eid = $added['element_id'];

        $this->assertTrue($this->execute('mcp-api-for-elementor/update-element', [
            'post_id' => $page_id,
            'element_id' => $eid,
            'settings' => ['text' => 'Gone'],
        ])['success']);

        $this->assertTrue($this->execute('mcp-api-for-elementor/move-element', [
            'post_id' => $page_id,
            'element_id' => $eid,
            'position' => 0,
        ])['success']);

        $dup = $this->execute('mcp-api-for-elementor/duplicate-element', [
            'post_id' => $page_id,
            'element_id' => $eid,
        ]);
        $this->assertNotEmpty($dup['new_id']);

        $gen = $this->execute('mcp-api-for-elementor/generate-element', [
            'type' => 'heading',
            'title' => 'Gen',
        ]);
        $this->assertSame('heading', $gen['widgetType']);

        $this->assertTrue($this->execute('mcp-api-for-elementor/remove-element', [
            'post_id' => $page_id,
            'element_id' => $eid,
        ])['success']);

        $this->assertArrayHasKey('items', $this->execute('mcp-api-for-elementor/list-templates'));

        $tpl = $this->execute('mcp-api-for-elementor/create-template', [
            'title' => 'Hdr',
            'type' => 'header',
            'data' => $this->sample_tree(),
        ]);
        $this->assertArrayHasKey('post_id', $tpl);

        $this->assertIsArray($this->execute('mcp-api-for-elementor/get-kit'));
        $this->assertTrue($this->execute('mcp-api-for-elementor/update-kit', [
            'settings' => ['custom_colors' => []],
        ])['success']);

        $this->assertNotEmpty($this->execute('mcp-api-for-elementor/list-widgets')['items']);
        $this->assertIsArray($this->execute('mcp-api-for-elementor/get-widget-schema', ['widget_name' => 'heading']));

        $this->assertTrue($this->execute('mcp-api-for-elementor/flush-css', ['post_id' => $page_id])['success']);

        $path = $this->drop_import_image('ability.png');
        $built = $this->execute('mcp-api-for-elementor/build-page', [
            'title' => 'Built Ability',
            'data' => $this->sample_tree(),
            'images' => [
                ['source_path' => $path, 'title' => 'AbilityImg'],
            ],
        ]);
        $this->assertArrayHasKey('post_id', $built);
        $this->assertArrayHasKey('AbilityImg', $built['image_ids']);
    }
}
