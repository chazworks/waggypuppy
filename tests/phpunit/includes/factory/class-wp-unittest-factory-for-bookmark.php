<?php

/**
 * Factory for creating fixtures for the deprecated Links/Bookmarks API.
 *
 * Note: The below @method notations are defined solely for the benefit of IDEs,
 * as a way to indicate expected return values from the given factory methods.
 *
 * @since 4.6.0
 *
 * @method int|WP_Error     create($args = [], $generation_definitions = null)
 * @method object|WP_Error  create_and_get($args = [], $generation_definitions = null)
 * @method (int|WP_Error)[] create_many($count, $args = array(), $generation_definitions = null)
 */
class WP_UnitTest_Factory_For_Bookmark extends WP_UnitTest_Factory_For_Thing
{

    public function __construct($factory = null)
    {
        parent::__construct($factory);
        $this->default_generation_definitions = [
            'link_name' => new WP_UnitTest_Generator_Sequence('Bookmark name %s'),
            'link_url' => new WP_UnitTest_Generator_Sequence('Bookmark URL %s'),
        ];
    }

    /**
     * Creates a link object.
     *
     * @param array $args Arguments for the link object.
     *
     * @return int|WP_Error The link ID on success, WP_Error object on failure.
     * @since 4.6.0
     * @since 6.2.0 Returns a WP_Error object on failure.
     *
     */
    public function create_object($args)
    {
        return wp_insert_link($args, true);
    }

    /**
     * Updates a link object.
     *
     * @param int $link_id ID of the link to update.
     * @param array $fields The fields to update.
     *
     * @return int|WP_Error The link ID on success, WP_Error object on failure.
     * @since 6.2.0 Returns a WP_Error object on failure.
     *
     * @since 4.6.0
     */
    public function update_object($link_id, $fields)
    {
        $fields['link_id'] = $link_id;

        $result = wp_update_link($fields);

        if (0 === $result) {
            return new WP_Error('link_update_error', __('Could not update link.'));
        }

        return $result;
    }

    /**
     * Retrieves a link by a given ID.
     *
     * @param int $link_id ID of the link to retrieve.
     *
     * @return object|null The link object on success, null on failure.
     * @since 4.6.0
     *
     */
    public function get_object_by_id($link_id)
    {
        return get_bookmark($link_id);
    }
}
