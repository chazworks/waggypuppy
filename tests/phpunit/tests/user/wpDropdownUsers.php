<?php

/**
 * Test functions in wp-includes/user.php
 *
 * @group user
 */
class Tests_User_wpDropdownUsers extends WP_UnitTestCase
{

    /**
     * @ticket 31251
     */
    public function test_default_value_of_show_should_be_display_name()
    {
        // Create a user with a different display_name.
        $u = self::factory()->user->create(
            [
                'user_login' => 'foo',
                'display_name' => 'Foo Person',
            ],
        );

        $found = wp_dropdown_users(
            [
                'echo' => false,
            ],
        );

        $expected = "<option value='$u'>Foo Person</option>";

        $this->assertStringContainsString($expected, $found);
    }

    /**
     * @ticket 31251
     */
    public function test_show_should_display_display_name_show_is_specified_as_empty()
    {
        // Create a user with a different display_name.
        $u = self::factory()->user->create(
            [
                'user_login' => 'foo',
                'display_name' => 'Foo Person',
            ],
        );

        // Get the result of a non-default, but acceptable input for 'show' parameter to wp_dropdown_users().
        $found = wp_dropdown_users(
            [
                'echo' => false,
                'show' => '',
            ],
        );

        $expected = "<option value='$u'>Foo Person</option>";

        $this->assertStringContainsString($expected, $found);
    }

    /**
     * @ticket 31251
     */
    public function test_show_should_display_user_property_when_the_value_of_show_is_a_valid_user_property()
    {
        // Create a user with a different display_name.
        $u = self::factory()->user->create(
            [
                'user_login' => 'foo',
                'display_name' => 'Foo Person',
            ],
        );

        // Get the result of a non-default, but acceptable input for 'show' parameter to wp_dropdown_users().
        $found = wp_dropdown_users(
            [
                'echo' => false,
                'show' => 'user_login',
            ],
        );

        $expected = "<option value='$u'>foo</option>";

        $this->assertStringContainsString($expected, $found);
    }

    /**
     * @ticket 31251
     */
    public function test_show_display_name_with_login()
    {
        // Create a user with a different display_name.
        $u = self::factory()->user->create(
            [
                'user_login' => 'foo',
                'display_name' => 'Foo Person',
            ],
        );

        // Get the result of a non-default, but acceptable input for 'show' parameter to wp_dropdown_users().
        $found = wp_dropdown_users(
            [
                'echo' => false,
                'show' => 'display_name_with_login',
            ],
        );

        $expected = "<option value='$u'>Foo Person (foo)</option>";

        $this->assertStringContainsString($expected, $found);
    }

    /**
     * @ticket 31251
     */
    public function test_include_selected()
    {
        $users = self::factory()->user->create_many(2);

        $found = wp_dropdown_users(
            [
                'echo' => false,
                'include' => $users[0],
                'selected' => $users[1],
                'include_selected' => true,
                'show' => 'user_login',
            ],
        );

        $user1 = get_userdata($users[1]);
        $this->assertStringContainsString($user1->user_login, $found);
    }

    /**
     * @ticket 51370
     */
    public function test_include_selected_with_non_existing_user_id()
    {
        $found = wp_dropdown_users(
            [
                'echo' => false,
                'selected' => PHP_INT_MAX,
                'include_selected' => true,
                'show' => 'user_login',
            ],
        );

        $this->assertStringNotContainsString((string)PHP_INT_MAX, $found);
    }

    /**
     * @ticket 38135
     */
    public function test_role()
    {
        $u1 = self::factory()->user->create_and_get(['role' => 'subscriber']);
        $u2 = self::factory()->user->create_and_get(['role' => 'author']);

        $found = wp_dropdown_users(
            [
                'echo' => false,
                'role' => 'author',
                'show' => 'user_login',
            ],
        );

        $this->assertStringNotContainsString($u1->user_login, $found);
        $this->assertStringContainsString($u2->user_login, $found);
    }

    /**
     * @ticket 38135
     */
    public function test_role__in()
    {
        $u1 = self::factory()->user->create_and_get(['role' => 'subscriber']);
        $u2 = self::factory()->user->create_and_get(['role' => 'author']);

        $found = wp_dropdown_users(
            [
                'echo' => false,
                'role__in' => ['author', 'editor'],
                'show' => 'user_login',
            ],
        );

        $this->assertStringNotContainsString($u1->user_login, $found);
        $this->assertStringContainsString($u2->user_login, $found);
    }

    /**
     * @ticket 38135
     */
    public function test_role__not_in()
    {
        $u1 = self::factory()->user->create_and_get(['role' => 'subscriber']);
        $u2 = self::factory()->user->create_and_get(['role' => 'author']);

        $found = wp_dropdown_users(
            [
                'echo' => false,
                'role__not_in' => ['subscriber', 'editor'],
                'show' => 'user_login',
            ],
        );

        $this->assertStringNotContainsString($u1->user_login, $found);
        $this->assertStringContainsString($u2->user_login, $found);
    }
}
