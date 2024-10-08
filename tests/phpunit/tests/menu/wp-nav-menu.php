<?php

/**
 * @group menu
 *
 * @covers ::wp_nav_menu
 */
class Tests_Menu_wpNavMenu extends WP_UnitTestCase {

    private static $menu_id        = 0;
    private static $lvl0_menu_item = 0;
    private static $lvl1_menu_item = 0;
    private static $lvl2_menu_item = 0;
    private static $lvl3_menu_item = 0;

    public static function set_up_before_class() {
        parent::set_up_before_class();

        // Create nav menu.
        self::$menu_id = wp_create_nav_menu( 'test' );

        // Create lvl0 menu item.
        self::$lvl0_menu_item = wp_update_nav_menu_item(
            self::$menu_id,
            0,
            array(
                'menu-item-title'  => 'Root menu item',
                'menu-item-url'    => '#',
                'menu-item-status' => 'publish',
            )
        );

        // Create lvl1 menu item.
        self::$lvl1_menu_item = wp_update_nav_menu_item(
            self::$menu_id,
            0,
            array(
                'menu-item-title'     => 'Lvl1 menu item',
                'menu-item-url'       => '#',
                'menu-item-parent-id' => self::$lvl0_menu_item,
                'menu-item-status'    => 'publish',
            )
        );

        // Create lvl2 menu item.
        self::$lvl2_menu_item = wp_update_nav_menu_item(
            self::$menu_id,
            0,
            array(
                'menu-item-title'     => 'Lvl2 menu item',
                'menu-item-url'       => '#',
                'menu-item-parent-id' => self::$lvl1_menu_item,
                'menu-item-status'    => 'publish',
            )
        );

        // Create lvl3 menu item.
        self::$lvl3_menu_item = wp_update_nav_menu_item(
            self::$menu_id,
            0,
            array(
                'menu-item-title'     => 'Lvl3 menu item',
                'menu-item-url'       => '#',
                'menu-item-parent-id' => self::$lvl2_menu_item,
                'menu-item-status'    => 'publish',
            )
        );

        /*
         * This filter is used to prevent reusing a menu item ID more that once.
         * It caused the tests to fail after the first one since the IDs are missing
         * from the HTML generated by `wp_nav_menu()`.
         *
         * To allow the tests to pass, we remove the filter before running them
         * and add it back after they ran ({@see Tests_Menu_wpNavMenu::tear_down_after_class()}).
         */
        remove_filter( 'nav_menu_item_id', '_nav_menu_item_id_use_once' );
    }

    public static function tear_down_after_class() {
        wp_delete_nav_menu( self::$menu_id );

        /*
         * This filter was removed to let the tests pass and needs to be added back
         * ({@see Tests_Menu_wpNavMenu::set_up_before_class}).
         */
        add_filter( 'nav_menu_item_id', '_nav_menu_item_id_use_once', 10, 2 );

        parent::tear_down_after_class();
    }

    /**
     * Tests that all menu items containing children have the CSS class `menu-item-has-children`
     * when displaying the menu without specifying a custom depth.
     *
     * @ticket 28620
     * @ticket 56946
     */
    public function test_wp_nav_menu_should_have_has_children_class_without_custom_depth() {

        // Render the menu with all its hierarchy.
        $menu_html = wp_nav_menu(
            array(
                'menu' => self::$menu_id,
                'echo' => false,
            )
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-%1$d">',
                self::$lvl0_menu_item
            ),
            $menu_html,
            'Level 0 should be present in the HTML output and have the `menu-item-has-children` class.'
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-%1$d">',
                self::$lvl1_menu_item
            ),
            $menu_html,
            'Level 1 should be present in the HTML output and have the `menu-item-has-children` class.'
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-%1$d">',
                self::$lvl2_menu_item
            ),
            $menu_html,
            'Level 2 should be present in the HTML output and have the `menu-item-has-children` class.'
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-%1$d">',
                self::$lvl3_menu_item
            ),
            $menu_html,
            'Level 3 should be present in the HTML output and not have the `menu-item-has-children` class since it has no children.'
        );
    }

    /**
     * Tests that when displaying a menu with a custom depth, the last menu item doesn't have the CSS class
     * `menu-item-has-children` even if it's the case when displaying the full menu.
     *
     * @ticket 28620
     * @ticket 56946
     */
    public function test_wp_nav_menu_should_not_have_has_children_class_with_custom_depth() {

        // Render the menu limited to 1 level of hierarchy (Lvl0 + Lvl1).
        $menu_html = wp_nav_menu(
            array(
                'menu'  => self::$menu_id,
                'depth' => 3,
                'echo'  => false,
            )
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-%1$d">',
                self::$lvl0_menu_item
            ),
            $menu_html,
            'Level 0 should be present in the HTML output and have the `menu-item-has-children` class.'
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-%1$d">',
                self::$lvl1_menu_item
            ),
            $menu_html,
            'Level 1 should be present in the HTML output and have the `menu-item-has-children` class.'
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-%1$d">',
                self::$lvl2_menu_item
            ),
            $menu_html,
            'Level 2 should be present in the HTML output and not have the `menu-item-has-children` class since it is the last item to be rendered.'
        );

        $this->assertStringNotContainsString(
            sprintf(
                '<li id="menu-item-%d"',
                self::$lvl3_menu_item
            ),
            $menu_html,
            'Level 3 should not be present in the HTML output.'
        );
    }

    /**
     * The order in which parent/child menu items are created should not matter.
     *
     * @ticket 57122
     */
    public function test_parent_with_higher_id_should_not_error() {
        // Create a new level zero menu item.
        $new_lvl0_menu_item = wp_update_nav_menu_item(
            self::$menu_id,
            0,
            array(
                'menu-item-title'  => 'Root menu item with high ID',
                'menu-item-url'    => '#',
                'menu-item-status' => 'publish',
            )
        );

        // Reparent level 1 menu item to the new level zero menu item.
        self::$lvl1_menu_item = wp_update_nav_menu_item(
            self::$menu_id,
            self::$lvl1_menu_item,
            array(
                'menu-item-parent-id' => $new_lvl0_menu_item,
            )
        );

        // Delete the old level zero menu item.
        wp_delete_post( self::$lvl0_menu_item, true );

        // Render the menu.
        $menu_html = wp_nav_menu(
            array(
                'menu' => self::$menu_id,
                'echo' => false,
            )
        );

        $this->assertStringContainsString(
            sprintf(
                '<li id="menu-item-%1$d" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-%1$d">',
                $new_lvl0_menu_item
            ),
            $menu_html,
            'The level zero menu item should appear in the menu.'
        );
    }
}
