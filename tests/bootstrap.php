<?php
/**
 * Minimal WordPress stubs so plugin files can load under PHPUnit.
 */

define('ABSPATH', __DIR__ . '/');

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return trailingslashit(dirname($file));
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('add_action')) {
    function add_action(...$args) {
    }
}

if (!function_exists('did_action')) {
    function did_action(...$args) {
        return 0;
    }
}

if (!function_exists('register_post_meta')) {
    function register_post_meta(...$args) {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(...$args) {
        return false;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = [];
        public $error_data = [];

        public function __construct($code = '', $message = '', $data = '') {
            if (empty($code)) {
                return;
            }
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_code() {
            $codes = array_keys($this->errors);
            return $codes[0] ?? '';
        }
    }
}

require_once dirname(__DIR__) . '/elementor-mcp-api.php';
