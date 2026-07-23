<?php

class ImportJailTest extends TestCase {

    public function test_staging_dir_and_jail_dirs() {
        $dir = McpApiForElementor\Elementor_Data::import_staging_dir();
        $this->assertDirectoryExists($dir);
        $this->assertFileExists(trailingslashit($dir) . 'index.php');

        $jails = McpApiForElementor\Elementor_Data::import_jail_dirs();
        $this->assertCount(2, $jails);
        $this->assertStringContainsString('mcp-api-for-elementor-import', $jails[0]);
        $this->assertStringContainsString('elementor-mcp-import', $jails[1]);
    }

    public function test_import_image_rejects_bad_inputs() {
        $this->assertSame(0, McpApiForElementor\Elementor_Data::import_image(''));
        $this->assertSame(0, McpApiForElementor\Elementor_Data::import_image('/tmp/nope-missing.png'));

        $outside = sys_get_temp_dir() . '/mcp-outside-' . getmypid() . '.png';
        file_put_contents($outside, $this->tiny_png_bytes());
        $this->assertSame(0, McpApiForElementor\Elementor_Data::import_image($outside));
        unlink($outside);

        $txt = $this->drop_import_image('notes.txt');
        file_put_contents($txt, 'hello');
        $this->assertSame(0, McpApiForElementor\Elementor_Data::import_image($txt));

        $forged = $this->drop_import_image('fake.png');
        file_put_contents($forged, 'not an image');
        $this->assertSame(0, McpApiForElementor\Elementor_Data::import_image($forged));
    }

    public function test_import_image_success_and_dedupe() {
        $path = $this->drop_import_image('hero.png');
        $id = McpApiForElementor\Elementor_Data::import_image($path, 'Hero Shot');
        $this->assertGreaterThan(0, $id);
        $this->assertNotEmpty(wp_get_attachment_url($id));

        $again = McpApiForElementor\Elementor_Data::import_image($path, 'Hero Shot');
        $this->assertSame($id, $again);

        $legacy = $this->drop_import_image(
            'legacy.png',
            trailingslashit(wp_upload_dir()['basedir']) . 'elementor-mcp-import'
        );
        $legacy_id = McpApiForElementor\Elementor_Data::import_image($legacy);
        $this->assertGreaterThan(0, $legacy_id);
    }
}
