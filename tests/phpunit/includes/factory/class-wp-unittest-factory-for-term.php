<?php

/**
 * Unit test factory for terms.
 *
 * Note: The below @method notations are defined solely for the benefit of IDEs,
 * as a way to indicate expected return values from the given factory methods.
 *
 * @method int|WP_Error          create($args = [], $generation_definitions = null)
 * @method WP_Term|WP_Error|null create_and_get($args = [], $generation_definitions = null)
 * @method (int|WP_Error)[]      create_many($count, $args = array(), $generation_definitions = null)
 */
class WP_UnitTest_Factory_For_Term extends WP_UnitTest_Factory_For_Thing
{

    private $taxonomy;
    const DEFAULT_TAXONOMY = 'post_tag';

    public function __construct($factory = null, $taxonomy = null)
    {
        parent::__construct($factory);
        $this->taxonomy = $taxonomy ? $taxonomy : self::DEFAULT_TAXONOMY;
        $this->default_generation_definitions = [
            'name' => new WP_UnitTest_Generator_Sequence('Term %s'),
            'taxonomy' => $this->taxonomy,
            'description' => new WP_UnitTest_Generator_Sequence('Term description %s'),
        ];
    }

    /**
     * Creates a term object.
     *
     * @param array $args Array of arguments for inserting a term.
     *
     * @return int|WP_Error The term ID on success, WP_Error object on failure.
     * @since UT (3.7.0)
     *
     */
    public function create_object($args)
    {
        $args = array_merge(['taxonomy' => $this->taxonomy], $args);
        $term_id_pair = wp_insert_term($args['name'], $args['taxonomy'], $args);

        if (is_wp_error($term_id_pair)) {
            return $term_id_pair;
        }

        return $term_id_pair['term_id'];
    }

    /**
     * Updates the term.
     *
     * @param int|object $term The term to update.
     * @param array $fields Array of arguments for updating a term.
     *
     * @return int|WP_Error The term ID on success, WP_Error object on failure.
     * @since 6.2.0 Returns a WP_Error object on failure.
     *
     * @since UT (3.7.0)
     */
    public function update_object($term, $fields)
    {
        $fields = array_merge(['taxonomy' => $this->taxonomy], $fields);

        if (is_object($term)) {
            $taxonomy = $term->taxonomy;
        }

        $term_id_pair = wp_update_term($term, $taxonomy, $fields);

        if (is_wp_error($term_id_pair)) {
            return $term_id_pair;
        }

        return $term_id_pair['term_id'];
    }

    /**
     * Attach terms to the given post.
     *
     * @param int $post_id The post ID.
     * @param string|array $terms An array of terms to set for the post, or a string of terms
     *                               separated by commas. Hierarchical taxonomies must always pass IDs rather
     *                               than names so that children with the same names but different parents
     *                               aren't confused.
     * @param string $taxonomy Taxonomy name.
     * @param bool $append Optional. If true, don't delete existing terms, just add on. If false,
     *                               replace the terms with the new terms. Default true.
     *
     * @return array|false|WP_Error Array of term taxonomy IDs of affected terms. WP_Error or false on failure.
     * @since UT (3.7.0)
     *
     */
    public function add_post_terms($post_id, $terms, $taxonomy, $append = true)
    {
        return wp_set_post_terms($post_id, $terms, $taxonomy, $append);
    }

    /**
     * Create a term and returns it as an object.
     *
     * @param array $args Array or string of arguments for inserting a term.
     * @param null $generation_definitions The default values.
     *
     * @return WP_Term|WP_Error|null WP_Term on success. WP_Error if taxonomy does not exist. Null for miscellaneous failure.
     * @since 4.3.0
     *
     */
    public function create_and_get($args = [], $generation_definitions = null)
    {
        $term_id = $this->create($args, $generation_definitions);

        if (is_wp_error($term_id)) {
            return $term_id;
        }

        $taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : $this->taxonomy;

        return get_term($term_id, $taxonomy);
    }

    /**
     * Retrieves the term by a given ID.
     *
     * @param int $term_id ID of the term to retrieve.
     *
     * @return WP_Term|WP_Error|null WP_Term on success. WP_Error if taxonomy does not exist. Null for miscellaneous failure.
     * @since UT (3.7.0)
     *
     */
    public function get_object_by_id($term_id)
    {
        return get_term($term_id, $this->taxonomy);
    }
}
