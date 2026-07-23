<?php

class PermissionsTest extends TestCase {

    public function test_capability_helpers() {
        $this->grant_caps([]);
        $this->assertFalse(McpApiForElementor\Permissions::can_read());
        $this->assertFalse(McpApiForElementor\Permissions::can_edit());
        $this->assertFalse(McpApiForElementor\Permissions::can_manage());
        $this->assertFalse(McpApiForElementor\Permissions::can_publish_pages());
        $this->assertFalse(McpApiForElementor\Permissions::can_upload());

        $this->grant_caps(['edit_pages']);
        $this->assertTrue(McpApiForElementor\Permissions::can_read());
        $this->assertTrue(McpApiForElementor\Permissions::can_edit());

        $this->grant_caps(['manage_options']);
        $this->assertTrue(McpApiForElementor\Permissions::can_manage());

        $this->grant_caps(['publish_pages']);
        $this->assertTrue(McpApiForElementor\Permissions::can_publish_pages());

        $this->grant_caps(['upload_files']);
        $this->assertTrue(McpApiForElementor\Permissions::can_upload());
    }

    public function test_can_edit_page() {
        $this->grant_caps([]);
        $this->assertFalse(McpApiForElementor\Permissions::can_edit_page(0));
        $this->assertFalse(McpApiForElementor\Permissions::can_edit_page(1));

        $this->grant_caps(['edit_pages']);
        $this->assertTrue(McpApiForElementor\Permissions::can_edit_page(1));
    }

    public function test_authorize_page_status() {
        $this->grant_caps([]);
        $this->assertSame('draft', McpApiForElementor\Permissions::authorize_page_status(null));
        $this->assertSame('draft', McpApiForElementor\Permissions::authorize_page_status('draft'));
        $this->assertSame('pending', McpApiForElementor\Permissions::authorize_page_status('pending'));

        $invalid = McpApiForElementor\Permissions::authorize_page_status('nope');
        $this->assertInstanceOf(WP_Error::class, $invalid);
        $this->assertSame('invalid_status', $invalid->get_error_code());

        $denied = McpApiForElementor\Permissions::authorize_page_status('publish');
        $this->assertInstanceOf(WP_Error::class, $denied);
        $this->assertSame('cannot_publish', $denied->get_error_code());

        $this->grant_caps(['publish_pages']);
        $this->assertSame('publish', McpApiForElementor\Permissions::authorize_page_status('publish'));
        $this->assertSame('private', McpApiForElementor\Permissions::authorize_page_status('private'));
        $this->assertSame('future', McpApiForElementor\Permissions::authorize_page_status('future'));
    }

    public function test_authorize_upload() {
        $this->grant_caps([]);
        $err = McpApiForElementor\Permissions::authorize_upload();
        $this->assertInstanceOf(WP_Error::class, $err);

        $this->grant_caps(['upload_files']);
        $this->assertTrue(McpApiForElementor\Permissions::authorize_upload());
    }
}
