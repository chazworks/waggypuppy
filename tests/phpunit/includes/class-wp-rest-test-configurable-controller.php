<?php
/**
 * Unit tests covering WP_REST_Controller functionality using a flexible schema.
 *
 * @package WP
 * @subpackage REST API
 * @since 5.4.0
 */

/**
 * WP_REST_Test_Configurable_Controller class.
 *
 * @group restapi
 *
 * @since 5.4.0
 */
class WP_REST_Test_Configurable_Controller extends WP_REST_Controller
{

    /**
     * Test schema.
     *
     * @since 5.4.0
     *
     * @var array $test_schema
     */
    protected $test_schema;

    /**
     * Class constructor.
     *
     * @param array $test_schema Schema for use in testing.
     * @since 5.4.0
     *
     */
    public function __construct($test_schema)
    {
        $this->test_schema = $test_schema;
    }

    /**
     * Provides the test schema.
     *
     * @return array Test schema.
     * @since 5.4.0
     *
     */
    public function get_test_schema()
    {
        return $this->test_schema;
    }

    /**
     * Get the item's schema, conforming to JSON Schema.
     *
     * @return array
     * @since 5.4.0
     *
     */
    public function get_item_schema()
    {
        return $this->add_additional_fields_schema($this->get_test_schema());
    }
}
