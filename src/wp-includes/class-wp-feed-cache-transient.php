<?php
/**
 * Feed API: WP_Feed_Cache_Transient class
 *
 * @package WP
 * @subpackage Feed
 * @since 4.7.0
 */

/**
 * Core class used to implement feed cache transients.
 *
 * @since 2.8.0
 * @since 6.7.0 Now properly implements the SimplePie\Cache\Base interface.
 */
#[AllowDynamicProperties]
class WP_Feed_Cache_Transient implements SimplePie\Cache\Base
{

    /**
     * Holds the transient name.
     *
     * @since 2.8.0
     * @var string
     */
    public $name;

    /**
     * Holds the transient mod name.
     *
     * @since 2.8.0
     * @var string
     */
    public $mod_name;

    /**
     * Holds the cache duration in seconds.
     *
     * Defaults to 43200 seconds (12 hours).
     *
     * @since 2.8.0
     * @var int
     */
    public $lifetime = 43200;

    /**
     * Creates a new (transient) cache object.
     *
     * @param string $location URL location (scheme is used to determine handler).
     * @param string $name Unique identifier for cache object.
     * @param Base::TYPE_FEED|Base::TYPE_IMAGE $type Either `TYPE_FEED` ('spc') for SimplePie data,
     *                                                   or `TYPE_IMAGE` ('spi') for image data.
     * @since 2.8.0
     * @since 3.2.0 Updated to use a PHP5 constructor.
     * @since 6.7.0 Parameter names have been updated to be in line with the `SimplePie\Cache\Base` interface.
     *
     */
    public function __construct($location, $name, $type)
    {
        $this->name = 'feed_' . $name;
        $this->mod_name = 'feed_mod_' . $name;

        $lifetime = $this->lifetime;
        /**
         * Filters the transient lifetime of the feed cache.
         *
         * @param int $lifetime Cache duration in seconds. Default is 43200 seconds (12 hours).
         * @param string $name Unique identifier for the cache object.
         * @since 2.8.0
         *
         */
        $this->lifetime = apply_filters('wp_feed_cache_transient_lifetime', $lifetime, $name);
    }

    /**
     * Saves data to the transient.
     *
     * @param array|SimplePie\SimplePie $data Data to save. If passed a SimplePie object,
     *                                        only cache the `$data` property.
     * @return true Always true.
     * @since 2.8.0
     *
     */
    public function save($data)
    {
        if ($data instanceof SimplePie\SimplePie) {
            $data = $data->data;
        }

        set_transient($this->name, $data, $this->lifetime);
        set_transient($this->mod_name, time(), $this->lifetime);
        return true;
    }

    /**
     * Retrieves the data saved in the transient.
     *
     * @return array Data for `SimplePie::$data`.
     * @since 2.8.0
     *
     */
    public function load()
    {
        return get_transient($this->name);
    }

    /**
     * Gets mod transient.
     *
     * @return int Timestamp.
     * @since 2.8.0
     *
     */
    public function mtime()
    {
        return get_transient($this->mod_name);
    }

    /**
     * Sets mod transient.
     *
     * @return bool False if value was not set and true if value was set.
     * @since 2.8.0
     *
     */
    public function touch()
    {
        return set_transient($this->mod_name, time(), $this->lifetime);
    }

    /**
     * Deletes transients.
     *
     * @return true Always true.
     * @since 2.8.0
     *
     */
    public function unlink()
    {
        delete_transient($this->name);
        delete_transient($this->mod_name);
        return true;
    }
}
