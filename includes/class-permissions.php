<?php
namespace McpApiForElementor;

/**
 * Centralized capability checks for REST + MCP abilities.
 */
class Permissions {

    /** Read Elementor page data / widgets (Editor+). */
    public static function can_read(): bool {
        return current_user_can('edit_pages');
    }

    /** Mutate pages/elements (Editor+). */
    public static function can_edit(): bool {
        return current_user_can('edit_pages');
    }

    /** Site-wide kit, Theme Builder templates, CSS flush (Admin). */
    public static function can_manage(): bool {
        return current_user_can('manage_options');
    }

    /** Object-level check for a specific page/post. */
    public static function can_edit_page(int $post_id): bool {
        return $post_id > 0 && current_user_can('edit_post', $post_id);
    }

    public static function can_publish_pages(): bool {
        return current_user_can('publish_pages');
    }

    /**
     * Normalize page status. Defaults to draft.
     * publish / private / future require publish_pages.
     *
     * @return string|\WP_Error
     */
    public static function authorize_page_status(?string $status) {
        $status = $status ?: 'draft';
        $allowed = ['draft', 'pending', 'publish', 'private', 'future'];

        if (!in_array($status, $allowed, true)) {
            return new \WP_Error('invalid_status', 'Invalid post status.', ['status' => 400]);
        }

        if (in_array($status, ['publish', 'private', 'future'], true) && !self::can_publish_pages()) {
            return new \WP_Error(
                'cannot_publish',
                'The publish_pages capability is required to set this status.',
                ['status' => 403]
            );
        }

        return $status;
    }
}
