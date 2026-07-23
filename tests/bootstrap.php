<?php
/**
 * In-memory WordPress (+ light Elementor) stubs for PHPUnit.
 */

if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

define('ABSPATH', sys_get_temp_dir() . '/mcp-api-for-elementor-tests-abspath/');
if (!is_dir(ABSPATH)) {
    mkdir(ABSPATH, 0777, true);
}
if (!is_dir(ABSPATH . 'wp-admin/includes')) {
    mkdir(ABSPATH . 'wp-admin/includes', 0777, true);
}
if (!file_exists(ABSPATH . 'wp-admin/includes/image.php')) {
    file_put_contents(ABSPATH . 'wp-admin/includes/image.php', "<?php\n");
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

/** @var array{posts: array, meta: array, options: array, caps: list<string>, actions: array, abilities: array, ability_categories: array, rest_routes: array, next_post_id: int, upload_basedir: string, upload_baseurl: string} */
$GLOBALS['mcp_test_state'] = [
    'posts'              => [],
    'meta'               => [],
    'options'            => [],
    'caps'               => [],
    'actions'            => [],
    'abilities'          => [],
    'ability_categories' => [],
    'rest_routes'        => [],
    'next_post_id'       => 1,
    'upload_basedir'     => '',
    'upload_baseurl'     => 'http://example.test/wp-content/uploads',
    'attachment_urls'    => [],
];

function mcp_test_reset_state(): void {
    $upload = sys_get_temp_dir() . '/mcp-api-for-elementor-uploads-' . getmypid();
    if (is_dir($upload)) {
        mcp_test_rrmdir($upload);
    }
    mkdir($upload, 0777, true);
    mkdir($upload . '/mcp-api-for-elementor-import', 0777, true);
    mkdir($upload . '/elementor-mcp-import', 0777, true);

    $GLOBALS['mcp_test_state'] = [
        'posts'              => [],
        'meta'               => [],
        'options'            => [],
        'caps'               => [],
        'actions'            => [],
        'abilities'          => [],
        'ability_categories' => [],
        'rest_routes'        => [],
        'next_post_id'       => 1,
        'upload_basedir'     => $upload,
        'upload_baseurl'     => 'http://example.test/wp-content/uploads',
        'attachment_urls'    => [],
    ];
}

function mcp_test_rrmdir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            mcp_test_rrmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

mcp_test_reset_state();

// Keep legacy cap global used by UploadPermissionsTest in sync.
$GLOBALS['mcp_api_for_elementor_test_caps'] = &$GLOBALS['mcp_test_state']['caps'];

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return trailingslashit(dirname($file));
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim((string) $string, '/\\') . '/';
    }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit($string) {
        return rtrim((string) $string, '/\\');
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mcp_test_state']['actions'][$hook][] = $callback;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        $GLOBALS['mcp_test_state']['actions']['_did'][$hook] =
            ($GLOBALS['mcp_test_state']['actions']['_did'][$hook] ?? 0) + 1;
        foreach ($GLOBALS['mcp_test_state']['actions'][$hook] ?? [] as $callback) {
            $callback(...$args);
        }
    }
}

if (!function_exists('did_action')) {
    function did_action($hook) {
        return (int) ($GLOBALS['mcp_test_state']['actions']['_did'][$hook] ?? 0);
    }
}

if (!function_exists('register_post_meta')) {
    function register_post_meta(...$args) {
        return true;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = [], $override = false) {
        $GLOBALS['mcp_test_state']['rest_routes'][] = compact('namespace', 'route', 'args');
        return true;
    }
}

if (!function_exists('wp_register_ability_category')) {
    function wp_register_ability_category($slug, $args = []) {
        $GLOBALS['mcp_test_state']['ability_categories'][$slug] = $args;
        return true;
    }
}

if (!function_exists('wp_register_ability')) {
    function wp_register_ability($name, $args = []) {
        $GLOBALS['mcp_test_state']['abilities'][$name] = $args;
        return true;
    }
}

if (!function_exists('wp_get_ability')) {
    function wp_get_ability($name) {
        $args = $GLOBALS['mcp_test_state']['abilities'][$name] ?? null;
        if ($args === null) {
            return null;
        }
        return new class($name, $args) {
            private string $name;
            private array $args;
            public function __construct(string $name, array $args) {
                $this->name = $name;
                $this->args = $args;
            }
            public function get_label() {
                return $this->args['label'] ?? $this->name;
            }
            public function get_description() {
                return $this->args['description'] ?? '';
            }
            public function get_input_schema() {
                return $this->args['input_schema'] ?? [];
            }
        };
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability = '', ...$args) {
        $caps = $GLOBALS['mcp_test_state']['caps'] ?? [];
        if (in_array('*', $caps, true)) {
            return true;
        }
        // Object caps: edit_post with post id — treat as granted if edit_post or edit_pages is present.
        if ($capability === 'edit_post') {
            return in_array('edit_post', $caps, true) || in_array('edit_pages', $caps, true);
        }
        return in_array((string) $capability, $caps, true);
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = [];
        public $error_data = [];

        public function __construct($code = '', $message = '', $data = '') {
            if ($code === '' || $code === null) {
                return;
            }
            $this->errors[$code][] = $message;
            if ($data !== '' && $data !== null) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_code() {
            $codes = array_keys($this->errors);
            return $codes[0] ?? '';
        }

        public function get_error_message($code = '') {
            if ($code === '') {
                $code = $this->get_error_code();
            }
            return $this->errors[$code][0] ?? '';
        }

        public function get_error_data($code = '') {
            if ($code === '') {
                $code = $this->get_error_code();
            }
            return $this->error_data[$code] ?? null;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request implements ArrayAccess {
        private array $route_params;
        private array $json_params;
        private array $query_params;

        public function __construct(array $route_params = [], array $json_params = [], array $query_params = []) {
            $this->route_params = $route_params;
            $this->json_params  = $json_params;
            $this->query_params = $query_params;
        }

        public function get_json_params() {
            return $this->json_params;
        }

        public function get_param($key) {
            return $this->query_params[$key] ?? $this->route_params[$key] ?? null;
        }

        public function offsetExists($offset): bool {
            return array_key_exists($offset, $this->route_params);
        }

        public function offsetGet($offset): mixed {
            return $this->route_params[$offset] ?? null;
        }

        public function offsetSet($offset, $value): void {
            $this->route_params[$offset] = $value;
        }

        public function offsetUnset($offset): void {
            unset($this->route_params[$offset]);
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        private $data;
        private int $status;

        public function __construct($data = null, $status = 200) {
            $this->data   = $data;
            $this->status = (int) $status;
        }

        public function get_data() {
            return $this->data;
        }

        public function get_status() {
            return $this->status;
        }
    }
}

if (!class_exists('WP_Post')) {
    class WP_Post {
        public int $ID;
        public string $post_title;
        public string $post_name;
        public string $post_status;
        public string $post_type;
        public string $post_excerpt;
        public string $post_content;

        public function __construct(array $data) {
            $this->ID           = (int) ($data['ID'] ?? 0);
            $this->post_title   = (string) ($data['post_title'] ?? '');
            $this->post_name    = (string) ($data['post_name'] ?? '');
            $this->post_status  = (string) ($data['post_status'] ?? 'draft');
            $this->post_type    = (string) ($data['post_type'] ?? 'page');
            $this->post_excerpt = (string) ($data['post_excerpt'] ?? '');
            $this->post_content = (string) ($data['post_content'] ?? '');
        }
    }
}

if (!class_exists('WP_Query')) {
    class WP_Query {
        public array $posts = [];

        public function __construct($args = []) {
            $title = $args['title'] ?? null;
            $type  = $args['post_type'] ?? null;
            foreach ($GLOBALS['mcp_test_state']['posts'] as $post) {
                if ($type && $post->post_type !== $type) {
                    continue;
                }
                if ($title !== null && $post->post_title !== $title) {
                    continue;
                }
                $this->posts[] = $post->ID;
                break;
            }
        }
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags((string) $str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim((string) $str);
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        $title = strtolower(trim((string) $title));
        $title = preg_replace('/[^a-z0-9]+/', '-', $title);
        return trim((string) $title, '-');
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $key));
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string) {
        return strip_tags((string) $string);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('wp_slash')) {
    function wp_slash($value) {
        return $value;
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0) {
        if ($max < $min) {
            return mt_rand();
        }
        return mt_rand($min, $max);
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        $base = $GLOBALS['mcp_test_state']['upload_basedir'];
        $path = $base;
        return [
            'path'    => $path,
            'url'     => $GLOBALS['mcp_test_state']['upload_baseurl'],
            'subdir'  => '',
            'basedir' => $base,
            'baseurl' => $GLOBALS['mcp_test_state']['upload_baseurl'],
            'error'   => false,
        ];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        if (is_dir($target)) {
            return true;
        }
        return mkdir($target, 0777, true);
    }
}

if (!function_exists('wp_check_filetype')) {
    function wp_check_filetype($filename, $mimes = null) {
        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'txt'  => 'text/plain',
        ];
        $type = $map[$ext] ?? false;
        return ['ext' => $type ? $ext : false, 'type' => $type];
    }
}

if (!function_exists('wp_get_image_mime')) {
    function wp_get_image_mime($file) {
        if (!is_readable($file)) {
            return false;
        }
        $bytes = file_get_contents($file, false, null, 0, 16);
        if ($bytes === false) {
            return false;
        }
        if (str_starts_with($bytes, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($bytes, 'GIF8')) {
            return 'image/gif';
        }
        return false;
    }
}

if (!function_exists('wp_unique_filename')) {
    function wp_unique_filename($dir, $filename, $unique_filename_callback = null) {
        $dest = trailingslashit($dir) . $filename;
        if (!file_exists($dest)) {
            return $filename;
        }
        $info = pathinfo($filename);
        $i = 1;
        do {
            $candidate = ($info['filename'] ?? 'file') . '-' . $i . (isset($info['extension']) ? '.' . $info['extension'] : '');
            $i++;
        } while (file_exists(trailingslashit($dir) . $candidate));
        return $candidate;
    }
}

if (!function_exists('wp_delete_file')) {
    function wp_delete_file($file) {
        if (is_file($file)) {
            unlink($file);
        }
        return true;
    }
}

if (!function_exists('wp_generate_attachment_metadata')) {
    function wp_generate_attachment_metadata($attachment_id, $file) {
        return ['file' => basename((string) $file)];
    }
}

if (!function_exists('wp_update_attachment_metadata')) {
    function wp_update_attachment_metadata($attachment_id, $data) {
        update_post_meta((int) $attachment_id, '_wp_attachment_metadata', $data);
        return true;
    }
}

if (!function_exists('wp_insert_attachment')) {
    function wp_insert_attachment($args, $file = false) {
        $args = is_array($args) ? $args : [];
        $args['post_type'] = 'attachment';
        $id = wp_insert_post($args);
        if ($file && !is_wp_error($id)) {
            $GLOBALS['mcp_test_state']['attachment_urls'][$id] =
                trailingslashit($GLOBALS['mcp_test_state']['upload_baseurl']) . basename((string) $file);
            update_post_meta($id, '_wp_attached_file', basename((string) $file));
        }
        return $id;
    }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url($attachment_id) {
        return $GLOBALS['mcp_test_state']['attachment_urls'][(int) $attachment_id]
            ?? ('http://example.test/?attachment_id=' . (int) $attachment_id);
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return array_key_exists($option, $GLOBALS['mcp_test_state']['options'])
            ? $GLOBALS['mcp_test_state']['options'][$option]
            : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        $GLOBALS['mcp_test_state']['options'][$option] = $value;
        return true;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        $meta = $GLOBALS['mcp_test_state']['meta'][(int) $post_id] ?? [];
        if ($key === '') {
            return $meta;
        }
        if (!array_key_exists($key, $meta)) {
            return $single ? '' : [];
        }
        return $single ? $meta[$key] : [$meta[$key]];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
        $GLOBALS['mcp_test_state']['meta'][(int) $post_id][$meta_key] = $meta_value;
        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key, $meta_value = '') {
        unset($GLOBALS['mcp_test_state']['meta'][(int) $post_id][$meta_key]);
        return true;
    }
}

if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw') {
        if ($post instanceof WP_Post) {
            return $post;
        }
        $id = (int) $post;
        return $GLOBALS['mcp_test_state']['posts'][$id] ?? null;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0) {
        $p = get_post($post);
        return $p ? $p->post_title : '';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0, $leavename = false) {
        $p = get_post($post);
        if (!$p) {
            return false;
        }
        return 'http://example.test/' . $p->post_name;
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = null) {
        $args = is_array($args) ? $args : [];
        $type = $args['post_type'] ?? 'post';
        $statuses = $args['post_status'] ?? ['publish'];
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }
        $out = [];
        foreach ($GLOBALS['mcp_test_state']['posts'] as $post) {
            if ($post->post_type !== $type) {
                continue;
            }
            if (!in_array($post->post_status, $statuses, true)) {
                continue;
            }
            $out[] = $post;
        }
        return $out;
    }
}

if (!function_exists('get_pages')) {
    function get_pages($args = []) {
        $args = is_array($args) ? $args : [];
        $statuses = $args['post_status'] ?? ['publish'];
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }
        $out = [];
        foreach ($GLOBALS['mcp_test_state']['posts'] as $post) {
            if ($post->post_type !== 'page') {
                continue;
            }
            if (!in_array($post->post_status, $statuses, true)) {
                continue;
            }
            $out[] = $post;
        }
        return $out;
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr, $wp_error = false, $fire_after_hooks = true) {
        $id = $GLOBALS['mcp_test_state']['next_post_id']++;
        $data = array_merge([
            'ID'           => $id,
            'post_title'   => '',
            'post_name'    => '',
            'post_status'  => 'draft',
            'post_type'    => 'post',
            'post_excerpt' => '',
            'post_content' => '',
        ], is_array($postarr) ? $postarr : []);
        $data['ID'] = $id;
        if ($data['post_name'] === '' && $data['post_title'] !== '') {
            $data['post_name'] = sanitize_title($data['post_title']);
        }
        $post = new WP_Post($data);
        $GLOBALS['mcp_test_state']['posts'][$id] = $post;
        return $id;
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($postarr, $wp_error = false, $fire_after_hooks = true) {
        $id = (int) ($postarr['ID'] ?? 0);
        $existing = get_post($id);
        if (!$existing) {
            return $wp_error ? new WP_Error('invalid_post', 'Invalid post.') : 0;
        }
        foreach (['post_title', 'post_name', 'post_status', 'post_excerpt', 'post_content', 'post_type'] as $field) {
            if (array_key_exists($field, $postarr)) {
                $existing->$field = $postarr[$field];
            }
        }
        $GLOBALS['mcp_test_state']['posts'][$id] = $existing;
        return $id;
    }
}

if (!function_exists('get_post_status')) {
    function get_post_status($post = null) {
        $p = get_post($post);
        return $p ? $p->post_status : false;
    }
}

require_once __DIR__ . '/stubs/elementor.php';
require_once __DIR__ . '/stubs/mcp-adapter.php';

if (!defined('ELEMENTOR_VERSION')) {
    define('ELEMENTOR_VERSION', '3.35.7');
}

\Elementor\Plugin::$instance = \Elementor\Plugin::instance();

require_once dirname(__DIR__) . '/mcp-api-for-elementor.php';
require_once __DIR__ . '/TestCase.php';
