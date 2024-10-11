<?php

/**
 * @group admin
 * @group network-admin
 * @group ms-required
 *
 * @covers WP_MS_Users_List_Table
 */
class Tests_Multisite_wpMsUsersListTable extends WP_UnitTestCase
{
    protected static $site_ids;

    /**
     * @var WP_MS_Users_List_Table
     */
    public $table = false;

    public function set_up()
    {
        parent::set_up();
        $this->table = _get_list_table('WP_MS_Users_List_Table', ['screen' => 'ms-users']);
    }

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$site_ids = [
            'wp.org/'          => [
                'domain' => 'wp.org',
                'path'   => '/',
            ],
            'wp.org/foo/'      => [
                'domain' => 'wp.org',
                'path'   => '/foo/',
            ],
            'wp.org/foo/bar/'  => [
                'domain' => 'wp.org',
                'path'   => '/foo/bar/',
            ],
            'wp.org/afoo/'     => [
                'domain' => 'wp.org',
                'path'   => '/afoo/',
            ],
            'make.wp.org/'     => [
                'domain' => 'make.wp.org',
                'path'   => '/',
            ],
            'make.wp.org/foo/' => [
                'domain' => 'make.wp.org',
                'path'   => '/foo/',
            ],
            'www.w.org/'              => [
                'domain' => 'www.w.org',
                'path'   => '/',
            ],
            'www.w.org/foo/'          => [
                'domain' => 'www.w.org',
                'path'   => '/foo/',
            ],
            'www.w.org/foo/bar/'      => [
                'domain' => 'www.w.org',
                'path'   => '/foo/bar/',
            ],
            'test.example.org/'       => [
                'domain' => 'test.example.org',
                'path'   => '/',
            ],
            'test2.example.org/'      => [
                'domain' => 'test2.example.org',
                'path'   => '/',
            ],
            'test3.example.org/zig/'  => [
                'domain' => 'test3.example.org',
                'path'   => '/zig/',
            ],
            'atest.example.org/'      => [
                'domain' => 'atest.example.org',
                'path'   => '/',
            ],
        ];

        foreach (self::$site_ids as &$id) {
            $id = $factory->blog->create($id);
        }
        unset($id);
    }

    public static function wpTearDownAfterClass()
    {
        foreach (self::$site_ids as $site_id) {
            wp_delete_site($site_id);
        }
    }

    /**
     * @ticket 42066
     *
     * @covers WP_MS_Users_List_Table::get_views
     */
    public function test_get_views_should_return_views_by_default()
    {
        $all   = get_user_count();
        $super = count(get_super_admins());

        $expected = [
            'all'   => '<a href="http://' . WP_TESTS_DOMAIN . '/wp-admin/network/users.php" class="current" aria-current="page">All <span class="count">(' . $all . ')</span></a>',
            'super' => '<a href="http://' . WP_TESTS_DOMAIN . '/wp-admin/network/users.php?role=super">Super Admin <span class="count">(' . $super . ')</span></a>',
        ];

        $this->assertSame($expected, $this->table->get_views());
    }
}
