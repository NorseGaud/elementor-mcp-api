<?php

class ElementorDataTest extends TestCase {

    public function test_get_and_save_page_data_via_document_api() {
        $id = $this->insert_page('Page', []);
        $tree = $this->sample_tree();
        $this->assertTrue(McpApiForElementor\Elementor_Data::save_page_data($id, $tree));
        $this->assertSame('builder', get_post_meta($id, '_elementor_edit_mode', true));
        $loaded = McpApiForElementor\Elementor_Data::get_page_data($id);
        $this->assertSame('root0001', $loaded[0]['id']);
    }

    public function test_get_page_data_meta_fallback_without_document() {
        $id = wp_insert_post(['post_title' => 'Orphan', 'post_type' => 'page', 'post_status' => 'draft']);
        // Force meta-only path by using invalid document lookup — still has get_post so document works.
        // Empty meta returns null.
        $this->assertNull(McpApiForElementor\Elementor_Data::get_page_data($id));
        update_post_meta($id, '_elementor_data', 'not-json');
        // Invalid JSON via document get_elements_data returns [] then falls through... document returns [].
        // Empty array is empty() so falls through to meta; meta is string "not-json" -> null decode.
        $this->assertNull(McpApiForElementor\Elementor_Data::get_page_data($id));
    }

    public function test_update_page_meta_and_yoast() {
        $id = $this->insert_page('Old Title');
        $result = McpApiForElementor\Elementor_Data::update_page_meta($id, [
            'title' => 'New Title',
            'slug' => 'new-slug',
            'excerpt' => 'Excerpt',
            'status' => 'draft',
            'focus_keyphrase' => 'kw',
            'seo_title' => 'SEO',
            'meta_description' => 'Desc',
            'og_title' => 'OG',
            'og_description' => 'OGD',
            'twitter_title' => 'TW',
            'twitter_description' => 'TWD',
        ]);
        $this->assertIsArray($result);
        $this->assertSame('New Title', $result['title']);
        $this->assertSame('new-slug', $result['slug']);
        $this->assertSame('kw', $result['focus_keyphrase']);

        $cleared = McpApiForElementor\Elementor_Data::update_page_meta($id, [
            'focus_keyphrase' => '',
        ]);
        $this->assertSame('', $cleared['focus_keyphrase']);

        $missing = McpApiForElementor\Elementor_Data::update_page_meta(99999, ['title' => 'x']);
        $this->assertInstanceOf(WP_Error::class, $missing);

        $this->grant_caps(['edit_pages']); // no publish
        $pub = McpApiForElementor\Elementor_Data::update_page_meta($id, ['status' => 'publish']);
        $this->assertInstanceOf(WP_Error::class, $pub);
    }

    public function test_page_settings_and_kit() {
        $id = $this->insert_page('Settings');
        $this->assertSame([], McpApiForElementor\Elementor_Data::get_page_settings(999));

        $updated = McpApiForElementor\Elementor_Data::update_page_settings($id, [
            'background_color' => '#fff',
            '__globals__' => ['background_color' => 'globals/colors?id=a'],
        ], ['legacy_key']);
        $this->assertSame('#fff', $updated['background_color']);

        update_post_meta($id, '_elementor_page_settings', array_merge($updated, ['legacy_key' => 1]));
        $updated2 = McpApiForElementor\Elementor_Data::update_page_settings($id, [], ['legacy_key']);
        $this->assertArrayNotHasKey('legacy_key', $updated2);

        $err = McpApiForElementor\Elementor_Data::update_page_settings(999, ['a' => 1]);
        $this->assertInstanceOf(WP_Error::class, $err);

        $this->assertSame([], McpApiForElementor\Elementor_Data::get_kit_settings());
        $this->assertFalse(McpApiForElementor\Elementor_Data::update_kit_settings(['x' => 1]));

        $kit_id = wp_insert_post(['post_title' => 'Kit', 'post_type' => 'elementor_library', 'post_status' => 'publish']);
        update_option('elementor_active_kit', $kit_id);
        update_post_meta($kit_id, '_elementor_page_settings', ['system_colors' => []]);
        $this->assertTrue(McpApiForElementor\Elementor_Data::update_kit_settings(['custom_colors' => []]));
        $kit = McpApiForElementor\Elementor_Data::get_kit_settings();
        $this->assertArrayHasKey('custom_colors', $kit);
    }

    public function test_create_template_writes_pro_conditions_option() {
        $post_id = McpApiForElementor\Elementor_Data::create_template(
            'Header',
            'header',
            $this->sample_tree(),
            ['include/general']
        );
        $this->assertGreaterThan(0, $post_id);
        $this->assertSame('header', get_post_meta($post_id, '_elementor_template_type', true));
        $all = get_option('elementor_pro_theme_builder_conditions');
        $this->assertSame(['include/general'], $all['header'][$post_id]);
    }

    public function test_widget_discovery_and_defaults() {
        Elementor\Plugin::$instance->widgets_manager->widgets['heading'] = new Elementor\Widget_Base(
            'heading',
            'Heading',
            [
                '_section' => ['type' => 'section'],
                'title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Hello'],
                'size' => ['type' => 'select', 'label' => 'Size', 'default' => 'default', 'options' => ['default' => 'Default']],
                'empty' => ['type' => 'text', 'default' => ''],
                'tabby' => ['type' => 'tab', 'default' => 'x'],
            ]
        );

        $list = McpApiForElementor\Elementor_Data::list_widgets();
        $this->assertCount(1, $list);
        $this->assertSame('heading', $list[0]['name']);

        $schema = McpApiForElementor\Elementor_Data::get_widget_schema('heading');
        $this->assertArrayHasKey('title', $schema);
        $this->assertArrayNotHasKey('_section', $schema);
        $this->assertArrayHasKey('options', $schema['size']);
        $this->assertNull(McpApiForElementor\Elementor_Data::get_widget_schema('missing'));

        $defaults = McpApiForElementor\Elementor_Data::get_widget_defaults('heading');
        $this->assertSame('heading', $defaults['widgetType']);
        $this->assertSame('Hello', $defaults['settings']['title']);
        $this->assertArrayNotHasKey('empty', $defaults['settings']);
        $this->assertNull(McpApiForElementor\Elementor_Data::get_widget_defaults('missing'));
    }

    public function test_flush_css() {
        $id = $this->insert_page('CSS');
        $css_dir = trailingslashit(wp_upload_dir()['basedir']) . 'elementor/css';
        wp_mkdir_p($css_dir);
        $css_path = $css_dir . '/post-' . $id . '.css';
        file_put_contents($css_path, 'body{}');
        update_post_meta($id, '_elementor_css', ['time' => 1]);

        McpApiForElementor\Elementor_Data::flush_css($id);
        $this->assertFileDoesNotExist($css_path);
        $this->assertSame('', get_post_meta($id, '_elementor_css', true));

        $before = Elementor\Plugin::$instance->files_manager->cleared;
        McpApiForElementor\Elementor_Data::flush_all_css();
        $this->assertSame($before + 1, Elementor\Plugin::$instance->files_manager->cleared);
    }
}
