<?php

class RestControllerTest extends TestCase {

    private McpApiForElementor\REST_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->controller = new McpApiForElementor\REST_Controller();
    }

    public function test_register_routes_and_basic_permissions() {
        $this->controller->register_routes();
        $this->assertNotEmpty($GLOBALS['mcp_test_state']['rest_routes']);

        foreach ($GLOBALS['mcp_test_state']['rest_routes'] as $route) {
            $this->assertArrayHasKey(
                'permission_callback',
                $route['args'],
                'Route ' . $route['namespace'] . $route['route'] . ' must declare permission_callback'
            );
            $this->assertNotEmpty($route['args']['permission_callback']);
        }

        $this->grant_caps(['edit_pages']);
        $this->assertTrue($this->controller->check_read_permission());
        $this->assertTrue($this->controller->check_edit_permission());
        $this->assertFalse($this->controller->check_manage_permission());
        $this->grant_caps(['manage_options']);
        $this->assertTrue($this->controller->check_manage_permission());
    }

    public function test_page_crud_flow() {
        $create = $this->controller->create_page($this->rest([], [
            'title' => 'Built',
            'slug' => 'built',
            'status' => 'draft',
            'data' => $this->sample_tree(),
        ]));
        $this->assertSame(201, $create->get_status());
        $page_id = $create->get_data()['id'];

        $list = $this->controller->list_pages($this->rest());
        $this->assertSame(200, $list->get_status());
        $this->assertNotEmpty($list->get_data());

        $get = $this->controller->get_page($this->rest(['id' => $page_id]));
        $this->assertSame(200, $get->get_status());
        $this->assertSame('root0001', $get->get_data()['data'][0]['id']);

        $structure = $this->controller->get_page_structure($this->rest(['id' => $page_id]));
        $this->assertSame(200, $structure->get_status());

        $update = $this->controller->update_page($this->rest(['id' => $page_id], [
            'data' => $this->sample_tree(),
        ]));
        $this->assertSame(200, $update->get_status());

        $meta = $this->controller->update_page_meta($this->rest(['id' => $page_id], [
            'title' => 'Renamed',
        ]));
        $this->assertSame(200, $meta->get_status());
        $this->assertSame('Renamed', $meta->get_data()['title']);

        $settings = $this->controller->update_page_settings($this->rest(['id' => $page_id], [
            'settings' => ['background_color' => '#000'],
        ]));
        $this->assertSame(200, $settings->get_status());
        $got_settings = $this->controller->get_page_settings($this->rest(['id' => $page_id]));
        $this->assertSame('#000', $got_settings->get_data()['settings']['background_color']);
    }

    public function test_page_error_paths() {
        $forbidden = $this->controller->get_page($this->rest(['id' => 1]));
        $this->grant_caps([]);
        $forbidden = $this->controller->get_page($this->rest(['id' => 1]));
        $this->assertSame(403, $forbidden->get_status());

        $this->grant_caps(['edit_pages', 'edit_post']);
        $missing = $this->controller->get_page($this->rest(['id' => 1]));
        $this->assertSame(404, $missing->get_status());

        $bad_update = $this->controller->update_page($this->rest(['id' => $this->insert_page('x', [])], []));
        $this->assertSame(400, $bad_update->get_status());

        $this->grant_caps(['edit_pages']);
        $pub = $this->controller->create_page($this->rest([], ['title' => 'P', 'status' => 'publish']));
        $this->assertSame(403, $pub->get_status());

        $bad_settings = $this->controller->update_page_settings($this->rest(
            ['id' => $this->insert_page('S')],
            ['settings' => 'nope']
        ));
        $this->assertSame(400, $bad_settings->get_status());
    }

    public function test_element_operations() {
        $page_id = $this->insert_page('Els', $this->sample_tree());

        $get = $this->controller->get_element($this->rest([
            'id' => $page_id,
            'element_id' => 'head0001',
        ]));
        $this->assertSame(200, $get->get_status());

        $add = $this->controller->add_element($this->rest(['id' => $page_id], [
            'parent_id' => 'col00001',
            'element' => ['elType' => 'widget', 'widgetType' => 'button', 'settings' => ['text' => 'A']],
        ]));
        $this->assertSame(201, $add->get_status());
        $new_id = $add->get_data()['element_id'];

        $upd = $this->controller->update_element($this->rest(
            ['id' => $page_id, 'element_id' => $new_id],
            ['settings' => ['text' => 'B']]
        ));
        $this->assertSame(200, $upd->get_status());

        $dup = $this->controller->duplicate_element($this->rest([
            'id' => $page_id,
            'element_id' => $new_id,
        ]));
        $this->assertSame(201, $dup->get_status());

        $move = $this->controller->move_element($this->rest(
            ['id' => $page_id, 'element_id' => $new_id],
            ['parent_id' => null, 'position' => 0]
        ));
        $this->assertSame(200, $move->get_status());

        $bulk = $this->controller->patch_elements_bulk($this->rest(['id' => $page_id], [
            'patches' => [
                ['id' => 'head0001', 'settings' => ['title' => 'Bulk']],
                ['settings' => ['x' => 1]],
            ],
        ]));
        $this->assertSame(200, $bulk->get_status());
        $this->assertSame(1, $bulk->get_data()['applied']);

        $width = $this->controller->set_column_width($this->rest(
            ['id' => $page_id, 'element_id' => 'col00001'],
            ['percent' => 25, 'tablet' => 50, 'mobile' => 100]
        ));
        $this->assertSame(200, $width->get_status());

        $find = $this->controller->find_elements($this->rest(
            ['id' => $page_id],
            [],
            ['widget' => 'heading']
        ));
        $this->assertSame(200, $find->get_status());
        $this->assertGreaterThanOrEqual(1, $find->get_data()['count']);

        $section = $this->controller->add_section($this->rest(['id' => $page_id], [
            'section' => ['elType' => 'container', 'settings' => [], 'elements' => []],
        ]));
        $this->assertSame(201, $section->get_status());

        $rm = $this->controller->remove_element($this->rest([
            'id' => $page_id,
            'element_id' => 'head0001',
        ]));
        $this->assertSame(200, $rm->get_status());
    }

    public function test_templates_kit_widgets_media_build_flush() {
        Elementor\Plugin::$instance->widgets_manager->widgets['heading'] = new Elementor\Widget_Base(
            'heading',
            'Heading',
            ['title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Hi']]
        );

        $tpl = $this->controller->create_template($this->rest([], [
            'title' => 'Foot',
            'type' => 'footer',
            'data' => $this->sample_tree(),
        ]));
        $this->assertSame(201, $tpl->get_status());
        $list = $this->controller->list_templates($this->rest());
        $this->assertNotEmpty($list->get_data());

        $kit_id = wp_insert_post(['post_title' => 'Kit', 'post_type' => 'elementor_library', 'post_status' => 'publish']);
        update_option('elementor_active_kit', $kit_id);
        update_post_meta($kit_id, '_elementor_page_settings', []);
        $this->assertSame(200, $this->controller->update_kit($this->rest([], ['a' => 1]))->get_status());
        $this->assertSame(200, $this->controller->get_kit($this->rest())->get_status());

        $widgets = $this->controller->list_widgets($this->rest());
        $this->assertNotEmpty($widgets->get_data());
        $schema = $this->controller->get_widget_schema($this->rest(['name' => 'heading']));
        $this->assertSame(200, $schema->get_status());
        $defaults = $this->controller->get_widget_defaults($this->rest(['name' => 'heading']));
        $this->assertSame(200, $defaults->get_status());
        $this->assertSame(404, $this->controller->get_widget_schema($this->rest(['name' => 'nope']))->get_status());

        $path = $this->drop_import_image('m.png');
        $media = $this->controller->import_media($this->rest([], ['path' => $path, 'title' => 'M']));
        $this->assertSame(201, $media->get_status());
        $this->assertSame(400, $this->controller->import_media($this->rest([], []))->get_status());

        $page_id = $this->insert_page('FlushMe', $this->sample_tree());
        $this->assertSame(200, $this->controller->flush_css($this->rest([], ['post_id' => $page_id]))->get_status());
        $this->assertSame(200, $this->controller->flush_css($this->rest([], []))->get_status());

        $build = $this->controller->build_page($this->rest([], [
            'title' => 'Composite',
            'data' => $this->sample_tree(),
            'images' => [
                'hero' => ['path' => $path, 'title' => 'Hero'],
            ],
        ]));
        $this->assertSame(201, $build->get_status());
        $this->assertArrayHasKey('hero', $build->get_data()['media_map']);

        $update_existing = $this->controller->build_page($this->rest([], [
            'page_id' => $page_id,
            'data' => $this->sample_tree(),
        ]));
        $this->assertSame(201, $update_existing->get_status());
    }
}
