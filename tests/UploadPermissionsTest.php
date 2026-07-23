<?php

class UploadPermissionsTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->grant_caps([]);
    }

    public function test_can_upload_requires_upload_files() {
        $this->assertFalse(McpApiForElementor\Permissions::can_upload());

        $this->grant_caps(['upload_files']);
        $this->assertTrue(McpApiForElementor\Permissions::can_upload());
    }

    public function test_authorize_upload_errors_without_capability() {
        $result = McpApiForElementor\Permissions::authorize_upload();
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('cannot_upload', $result->get_error_code());
        $this->assertSame(403, $result->get_error_data()['status'] ?? null);
    }

    public function test_authorize_upload_allows_with_capability() {
        $this->grant_caps(['upload_files']);
        $this->assertTrue(McpApiForElementor\Permissions::authorize_upload());
    }

    public function test_can_build_page_allows_edit_without_images() {
        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';

        $this->grant_caps(['edit_pages']);
        $this->assertTrue(McpApiForElementor\Abilities_Provider::can_build_page(['data' => []]));
        $this->assertTrue(McpApiForElementor\Abilities_Provider::can_build_page(null));
    }

    public function test_can_build_page_requires_upload_when_images_present() {
        require_once dirname(__DIR__) . '/includes/class-abilities-provider.php';

        $this->grant_caps(['edit_pages']);
        $this->assertFalse(McpApiForElementor\Abilities_Provider::can_build_page([
            'data'   => [],
            'images' => [['source_path' => 'x.png']],
        ]));

        $this->grant_caps(['edit_pages', 'upload_files']);
        $this->assertTrue(McpApiForElementor\Abilities_Provider::can_build_page([
            'data'   => [],
            'images' => [['source_path' => 'x.png']],
        ]));
    }

    public function test_media_import_permission_requires_edit_and_upload() {
        $controller = new McpApiForElementor\REST_Controller();

        $this->grant_caps(['edit_pages']);
        $this->assertFalse($controller->check_media_import_permission());

        $this->grant_caps(['upload_files']);
        $this->assertFalse($controller->check_media_import_permission());

        $this->grant_caps(['edit_pages', 'upload_files']);
        $this->assertTrue($controller->check_media_import_permission());
    }

    public function test_build_page_permission_requires_upload_only_with_images() {
        $controller = new McpApiForElementor\REST_Controller();

        $this->grant_caps(['edit_pages']);
        $this->assertTrue($controller->check_build_page_permission($this->rest([], [
            'title' => 'Hello',
            'data'  => [],
        ])));
        $this->assertFalse($controller->check_build_page_permission($this->rest([], [
            'title'  => 'Hello',
            'data'   => [],
            'images' => [['path' => 'x.png']],
        ])));

        $this->grant_caps(['edit_pages', 'upload_files']);
        $this->assertTrue($controller->check_build_page_permission($this->rest([], [
            'title'  => 'Hello',
            'data'   => [],
            'images' => [['path' => 'x.png']],
        ])));
    }
}
