<?php
/**
 * Rest Font Collections Controller.
 *
 * This file contains the class for the REST API Font Collections Controller.
 *
 * @package WP
 * @subpackage REST_API
 * @since      6.5.0
 */

/**
 * Font Library Controller class.
 *
 * @since 6.5.0
 */
class WP_REST_Font_Collections_Controller extends WP_REST_Controller
{

    /**
     * Constructor.
     *
     * @since 6.5.0
     */
    public function __construct()
    {
        $this->rest_base = 'font-collections';
        $this->namespace = 'wp/v2';
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 6.5.0
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => $this->get_collection_params(),

                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<slug>[\/\w-]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );
    }

    /**
     * Gets the font collections available.
     *
     * @since 6.5.0
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $collections_all = WP_Font_Library::get_instance()->get_font_collections();

        $page        = $request['page'];
        $per_page    = $request['per_page'];
        $total_items = count($collections_all);
        $max_pages   = (int) ceil($total_items / $per_page);

        if ($page > $max_pages && $total_items > 0) {
            return new WP_Error(
                'rest_post_invalid_page_number',
                __('The page number requested is larger than the number of pages available.'),
                ['status' => 400]
            );
        }

        $collections_page = array_slice($collections_all, ($page - 1) * $per_page, $per_page);

        $items = [];
        foreach ($collections_page as $collection) {
            $item = $this->prepare_item_for_response($collection, $request);

            // If there's an error loading a collection, skip it and continue loading valid collections.
            if (is_wp_error($item)) {
                continue;
            }
            $item    = $this->prepare_response_for_collection($item);
            $items[] = $item;
        }

        $response = rest_ensure_response($items);

        $response->header('X-WP-Total', (int) $total_items);
        $response->header('X-WP-TotalPages', $max_pages);

        $request_params = $request->get_query_params();
        $collection_url = rest_url($this->namespace . '/' . $this->rest_base);
        $base           = add_query_arg(urlencode_deep($request_params), $collection_url);

        if ($page > 1) {
            $prev_page = $page - 1;

            if ($prev_page > $max_pages) {
                $prev_page = $max_pages;
            }

            $prev_link = add_query_arg('page', $prev_page, $base);
            $response->link_header('prev', $prev_link);
        }
        if ($max_pages > $page) {
            $next_page = $page + 1;
            $next_link = add_query_arg('page', $next_page, $base);

            $response->link_header('next', $next_link);
        }

        return $response;
    }

    /**
     * Gets a font collection.
     *
     * @since 6.5.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request)
    {
        $slug       = $request->get_param('slug');
        $collection = WP_Font_Library::get_instance()->get_font_collection($slug);

        if (! $collection) {
            return new WP_Error('rest_font_collection_not_found', __('Font collection not found.'), ['status' => 404]);
        }

        return $this->prepare_item_for_response($collection, $request);
    }

    /**
    * Prepare a single collection output for response.
    *
    * @since 6.5.0
    *
    * @param WP_Font_Collection $item    Font collection object.
    * @param WP_REST_Request    $request Request object.
    * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
    */
    public function prepare_item_for_response($item, $request)
    {
        $fields = $this->get_fields_for_response($request);
        $data   = [];

        if (rest_is_field_included('slug', $fields)) {
            $data['slug'] = $item->slug;
        }

        // If any data fields are requested, get the collection data.
        $data_fields = ['name', 'description', 'font_families', 'categories'];
        if (! empty(array_intersect($fields, $data_fields))) {
            $collection_data = $item->get_data();
            if (is_wp_error($collection_data)) {
                $collection_data->add_data(['status' => 500]);
                return $collection_data;
            }

            foreach ($data_fields as $field) {
                if (rest_is_field_included($field, $fields)) {
                    $data[$field] = $collection_data[$field];
                }
            }
        }

        $response = rest_ensure_response($data);

        if (rest_is_field_included('_links', $fields)) {
            $links = $this->prepare_links($item);
            $response->add_links($links);
        }

        $context        = ! empty($request['context']) ? $request['context'] : 'view';
        $response->data = $this->add_additional_fields_to_object($response->data, $request);
        $response->data = $this->filter_response_by_context($response->data, $context);

        /**
         * Filters the font collection data for a REST API response.
         *
         * @since 6.5.0
         *
         * @param WP_REST_Response   $response The response object.
         * @param WP_Font_Collection $item     The font collection object.
         * @param WP_REST_Request    $request  Request used to generate the response.
         */
        return apply_filters('rest_prepare_font_collection', $response, $item, $request);
    }

    /**
     * Retrieves the font collection's schema, conforming to JSON Schema.
     *
     * @since 6.5.0
     *
     * @return array Item schema data.
     */
    public function get_item_schema()
    {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'font-collection',
            'type'       => 'object',
            'properties' => [
                'slug'          => [
                    'description' => __('Unique identifier for the font collection.'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit', 'embed'],
                    'readonly'    => true,
                ],
                'name'          => [
                    'description' => __('The name for the font collection.'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit', 'embed'],
                ],
                'description'   => [
                    'description' => __('The description for the font collection.'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit', 'embed'],
                ],
                'font_families' => [
                    'description' => __('The font families for the font collection.'),
                    'type'        => 'array',
                    'context'     => ['view', 'edit', 'embed'],
                ],
                'categories'    => [
                    'description' => __('The categories for the font collection.'),
                    'type'        => 'array',
                    'context'     => ['view', 'edit', 'embed'],
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema($this->schema);
    }

    /**
     * Prepares links for the request.
     *
     * @since 6.5.0
     *
     * @param WP_Font_Collection $collection Font collection data
     * @return array Links for the given font collection.
     */
    protected function prepare_links($collection)
    {
        return [
            'self'       => [
                'href' => rest_url(sprintf('%s/%s/%s', $this->namespace, $this->rest_base, $collection->slug)),
            ],
            'collection' => [
                'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
            ],
        ];
    }

    /**
     * Retrieves the search params for the font collections.
     *
     * @since 6.5.0
     *
     * @return array Collection parameters.
     */
    public function get_collection_params()
    {
        $query_params = parent::get_collection_params();

        $query_params['context'] = $this->get_context_param(['default' => 'view']);

        unset($query_params['search']);

        /**
         * Filters REST API collection parameters for the font collections controller.
         *
         * @since 6.5.0
         *
         * @param array $query_params JSON Schema-formatted collection parameters.
         */
        return apply_filters('rest_font_collections_collection_params', $query_params);
    }

    /**
     * Checks whether the user has permissions to use the Fonts Collections.
     *
     * @since 6.5.0
     *
     * @return true|WP_Error True if the request has write access for the item, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request)
    {
        if (current_user_can('edit_theme_options')) {
            return true;
        }

        return new WP_Error(
            'rest_cannot_read',
            __('Sorry, you are not allowed to access font collections.'),
            [
                'status' => rest_authorization_required_code(),
            ]
        );
    }
}
