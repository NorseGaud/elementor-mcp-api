<?php

use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase {

    protected function setUp(): void {
        parent::setUp();
        mcp_test_reset_state();
        Elementor\Plugin::$instance = new Elementor\Plugin();
        $this->grant_caps(['edit_pages', 'edit_post', 'upload_files', 'publish_pages', 'manage_options']);
    }

    /** @param list<string> $caps */
    protected function grant_caps(array $caps): void {
        $GLOBALS['mcp_test_state']['caps'] = $caps;
    }

    protected function insert_page(string $title = 'Test Page', ?array $elementor_data = null, string $status = 'draft'): int {
        $id = wp_insert_post([
            'post_title'  => $title,
            'post_name'   => sanitize_title($title),
            'post_type'   => 'page',
            'post_status' => $status,
        ]);
        if ($elementor_data !== null) {
            update_post_meta($id, '_elementor_edit_mode', 'builder');
            update_post_meta($id, '_elementor_data', wp_json_encode($elementor_data));
        }
        return $id;
    }

    protected function sample_tree(): array {
        return [
            [
                'id'       => 'root0001',
                'elType'   => 'container',
                'settings' => ['content_width' => 'boxed', 'title' => 'Root'],
                'elements' => [
                    [
                        'id'         => 'head0001',
                        'elType'     => 'widget',
                        'widgetType' => 'heading',
                        'settings'   => ['title' => 'Hello World'],
                        'elements'   => [],
                    ],
                    [
                        'id'       => 'col00001',
                        'elType'   => 'container',
                        'isInner'  => true,
                        'settings' => ['flex_direction' => 'column'],
                        'elements' => [
                            [
                                'id'         => 'text0001',
                                'elType'     => 'widget',
                                'widgetType' => 'text-editor',
                                'settings'   => ['editor' => '<p>Body copy</p>'],
                                'elements'   => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function rest(array $route = [], array $json = [], array $query = []): WP_REST_Request {
        return new WP_REST_Request($route, $json, $query);
    }

    /** Minimal valid 1x1 PNG bytes. */
    protected function tiny_png_bytes(): string {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
    }

    protected function drop_import_image(string $filename = 'pixel.png', ?string $jail = null): string {
        $jail = $jail ?: (trailingslashit(wp_upload_dir()['basedir']) . 'mcp-api-for-elementor-import');
        if (!is_dir($jail)) {
            mkdir($jail, 0777, true);
        }
        $path = trailingslashit($jail) . $filename;
        file_put_contents($path, $this->tiny_png_bytes());
        return $path;
    }
}
