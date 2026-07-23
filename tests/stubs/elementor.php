<?php

namespace Elementor;

class Widget_Base {
    private string $name;
    private string $title;
    private array $controls;

    public function __construct(string $name, string $title, array $controls = []) {
        $this->name = $name;
        $this->title = $title;
        $this->controls = $controls;
    }

    public function get_title() {
        return $this->title;
    }

    public function get_icon() {
        return 'eicon-t-letter';
    }

    public function get_categories() {
        return ['basic'];
    }

    public function get_controls() {
        return $this->controls;
    }
}

class Widgets_Manager {
    /** @var array<string, Widget_Base> */
    public array $widgets = [];

    public function get_widget_types($name = null) {
        if ($name === null) {
            return $this->widgets;
        }
        return $this->widgets[$name] ?? false;
    }
}

class Files_Manager {
    public int $cleared = 0;

    public function clear_cache() {
        $this->cleared++;
    }
}

class Document {
    private int $post_id;

    public function __construct(int $post_id) {
        $this->post_id = $post_id;
    }

    public function get_elements_data() {
        $raw = \get_post_meta($this->post_id, '_elementor_data', true);
        if (empty($raw)) {
            return [];
        }
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        return is_array($data) ? $data : [];
    }

    public function save(array $data) {
        if (isset($data['elements'])) {
            \update_post_meta($this->post_id, '_elementor_data', \wp_json_encode($data['elements']));
        }
        return true;
    }
}

class Documents_Manager {
    public function get($post_id) {
        if (!\get_post($post_id)) {
            return false;
        }
        return new Document((int) $post_id);
    }
}

class Plugin {
    /** @var Plugin|null */
    public static $instance = null;
    public Widgets_Manager $widgets_manager;
    public Documents_Manager $documents;
    public Files_Manager $files_manager;

    public function __construct() {
        $this->widgets_manager = new Widgets_Manager();
        $this->documents = new Documents_Manager();
        $this->files_manager = new Files_Manager();
    }

    public static function instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
