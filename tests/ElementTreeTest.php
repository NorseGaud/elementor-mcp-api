<?php

class ElementTreeTest extends TestCase {

    public function test_find_update_remove_insert_duplicate() {
        $tree = $this->sample_tree();

        $found = McpApiForElementor\Elementor_Data::find_element($tree, 'text0001');
        $this->assertNotNull($found);
        $this->assertSame('text-editor', $found['element']['widgetType']);

        $this->assertTrue(
            McpApiForElementor\Elementor_Data::update_element_settings($tree, 'head0001', ['title' => 'Updated'])
        );
        $this->assertSame('Updated', $tree[0]['elements'][0]['settings']['title']);
        $this->assertFalse(
            McpApiForElementor\Elementor_Data::update_element_settings($tree, 'missing', ['x' => 1])
        );

        $widget = [
            'id'         => 'new00001',
            'elType'     => 'widget',
            'widgetType' => 'button',
            'settings'   => ['text' => 'Go'],
            'elements'   => [],
        ];
        McpApiForElementor\Elementor_Data::insert_element($tree[0]['elements'], $widget, 0);
        $this->assertSame('new00001', $tree[0]['elements'][0]['id']);

        McpApiForElementor\Elementor_Data::insert_element($tree[0]['elements'], [
            'id' => 'appended',
            'elType' => 'widget',
            'widgetType' => 'divider',
            'settings' => [],
            'elements' => [],
        ], -1);
        $this->assertSame('appended', $tree[0]['elements'][count($tree[0]['elements']) - 1]['id']);

        $new_id = McpApiForElementor\Elementor_Data::duplicate_element($tree, 'head0001');
        $this->assertNotNull($new_id);
        $this->assertNotSame('head0001', $new_id);
        $dup = McpApiForElementor\Elementor_Data::find_element($tree, $new_id);
        $this->assertNotNull($dup);
        $this->assertSame('Updated', $dup['element']['settings']['title']);

        $this->assertTrue(McpApiForElementor\Elementor_Data::remove_element($tree, 'text0001'));
        $this->assertNull(McpApiForElementor\Elementor_Data::find_element($tree, 'text0001'));
        $this->assertFalse(McpApiForElementor\Elementor_Data::remove_element($tree, 'missing'));
        $this->assertNull(McpApiForElementor\Elementor_Data::duplicate_element($tree, 'missing'));
    }

    public function test_merge_assoc_settings_edges() {
        $merged = McpApiForElementor\Elementor_Data::merge_assoc_settings(
            ['a' => 1, 'list' => [1, 2], 'nested' => ['x' => 1]],
            ['list' => [9], 'nested' => ['y' => 2], 'b' => 'z']
        );
        $this->assertSame([9], $merged['list']);
        $this->assertSame(1, $merged['nested']['x']);
        $this->assertSame(2, $merged['nested']['y']);
        $this->assertSame('z', $merged['b']);

        $empty_assoc = McpApiForElementor\Elementor_Data::merge_assoc_settings(
            ['nested' => []],
            ['nested' => ['k' => 'v']]
        );
        $this->assertSame(['k' => 'v'], $empty_assoc['nested']);
    }

    public function test_page_structure_summary() {
        $id = $this->insert_page('Struct', $this->sample_tree());
        $structure = McpApiForElementor\Elementor_Data::get_page_structure($id);
        $this->assertIsArray($structure);
        $this->assertSame('root0001', $structure[0]['id']);
        $this->assertSame('heading', $structure[0]['children'][0]['widgetType']);
        $this->assertArrayHasKey('hint', $structure[0]['children'][0]);
        $this->assertNull(McpApiForElementor\Elementor_Data::get_page_structure(9999));
    }
}
