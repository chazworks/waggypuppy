<?php

/**
 * Test the add_filter method of WP_Hook
 *
 * @group hooks
 * @covers WP_Hook::add_filter
 */
class Tests_Hooks_AddFilter extends WP_UnitTestCase
{

    public $hook;

    /**
     * Temporary storage for action output.
     *
     * Used in the following tests:
     * - `test_remove_and_add_action()`
     * - `test_remove_and_add_last_action()`
     * - `test_remove_and_recurse_and_add_action()`
     *
     * @var array
     */
    private $action_output = '';

    public function tear_down()
    {
        $this->action_output = '';
        parent::tear_down();
    }

    public function test_add_filter_with_function()
    {
        $callback = '__return_null';
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);

        $function_index = _wp_filter_build_unique_id($hook_name, $callback, $priority);
        $this->assertSame($callback, $hook->callbacks[$priority][$function_index]['function']);
        $this->assertSame($accepted_args, $hook->callbacks[$priority][$function_index]['accepted_args']);
    }

    public function test_add_filter_with_object()
    {
        $a = new MockAction();
        $callback = [$a, 'action'];
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);

        $function_index = _wp_filter_build_unique_id($hook_name, $callback, $priority);
        $this->assertSame($callback, $hook->callbacks[$priority][$function_index]['function']);
        $this->assertSame($accepted_args, $hook->callbacks[$priority][$function_index]['accepted_args']);
    }

    public function test_add_filter_with_static_method()
    {
        $callback = ['MockAction', 'action'];
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);

        $function_index = _wp_filter_build_unique_id($hook_name, $callback, $priority);
        $this->assertSame($callback, $hook->callbacks[$priority][$function_index]['function']);
        $this->assertSame($accepted_args, $hook->callbacks[$priority][$function_index]['accepted_args']);
    }

    public function test_add_two_filters_with_same_priority()
    {
        $callback_one = '__return_null';
        $callback_two = '__return_false';
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback_one, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);
        $this->assertCount(1, $hook->callbacks[$priority]);

        $hook->add_filter($hook_name, $callback_two, $priority, $accepted_args);
        $this->assertCount(2, $hook->callbacks[$priority]);
    }

    public function test_add_two_filters_with_different_priority()
    {
        $callback_one = '__return_null';
        $callback_two = '__return_false';
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback_one, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);
        $this->assertCount(1, $hook->callbacks[$priority]);

        $hook->add_filter($hook_name, $callback_two, $priority + 1, $accepted_args);
        $this->check_priority_exists($hook, $priority + 1);
        $this->assertCount(1, $hook->callbacks[$priority]);
        $this->assertCount(1, $hook->callbacks[$priority + 1]);
    }

    public function test_readd_filter()
    {
        $callback = '__return_null';
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);
        $this->assertCount(1, $hook->callbacks[$priority]);

        $hook->add_filter($hook_name, $callback, $priority, $accepted_args);
        $this->assertCount(1, $hook->callbacks[$priority]);
    }

    public function test_readd_filter_with_different_priority()
    {
        $callback = '__return_null';
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;
        $priority = 1;
        $accepted_args = 2;

        $hook->add_filter($hook_name, $callback, $priority, $accepted_args);
        $this->check_priority_exists($hook, $priority);
        $this->assertCount(1, $hook->callbacks[$priority]);

        $hook->add_filter($hook_name, $callback, $priority + 1, $accepted_args);
        $this->check_priority_exists($hook, $priority + 1);
        $this->assertCount(1, $hook->callbacks[$priority]);
        $this->assertCount(1, $hook->callbacks[$priority + 1]);
    }

    public function test_sort_after_add_filter()
    {
        $a = new MockAction();
        $b = new MockAction();
        $c = new MockAction();
        $hook = new WP_Hook();
        $hook_name = __FUNCTION__;

        $hook->add_filter($hook_name, [$a, 'action'], 10, 1);
        $hook->add_filter($hook_name, [$b, 'action'], 5, 1);
        $hook->add_filter($hook_name, [$c, 'action'], 8, 1);

        $this->assertSame([5, 8, 10], $this->get_priorities($hook));
    }

    public function test_remove_and_add()
    {
        $this->hook = new WP_Hook();

        $this->hook->add_filter('remove_and_add', '__return_empty_string', 10, 0);
        $this->check_priority_exists($this->hook, 10);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add2'], 11, 1);
        $this->check_priority_exists($this->hook, 11);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add4'], 12, 1);
        $this->check_priority_exists($this->hook, 12);
        $value = $this->hook->apply_filters('', []);

        $this->assertSameSets([10, 11, 12], $this->get_priorities($this->hook),
            'The priorities should match this array');

        $this->assertSame('24', $value);
    }

    public function test_remove_and_add_last_filter()
    {
        $this->hook = new WP_Hook();

        $this->hook->add_filter('remove_and_add', '__return_empty_string', 10, 0);
        $this->check_priority_exists($this->hook, 10);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add1'], 11, 1);
        $this->check_priority_exists($this->hook, 11);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add2'], 12, 1);
        $this->check_priority_exists($this->hook, 12);
        $value = $this->hook->apply_filters('', []);

        $this->assertSameSets([10, 11, 12], $this->get_priorities($this->hook),
            'The priorities should match this array');

        $this->assertSame('12', $value);
    }

    public function test_remove_and_recurse_and_add()
    {
        $this->hook = new WP_Hook();

        $this->hook->add_filter('remove_and_add', '__return_empty_string', 10, 0);

        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add1'], 11, 1);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_recurse_and_add2'], 11, 1);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add3'], 11, 1);

        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add4'], 12, 1);

        $this->assertSameSets([10, 11, 12], $this->get_priorities($this->hook),
            'The priorities should match this array');

        $value = $this->hook->apply_filters('', []);

        $this->assertSame('1-134-234', $value);
    }

    public function _filter_remove_and_add1($value)
    {
        return $value . '1';
    }

    public function _filter_remove_and_add2($value)
    {
        $this->hook->remove_filter('remove_and_add', [$this, '_filter_remove_and_add2'], 11);
        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_add2'], 11, 1);
        $this->check_priority_exists($this->hook, 11);
        return $value . '2';
    }

    public function _filter_remove_and_recurse_and_add2($value)
    {
        $this->hook->remove_filter('remove_and_add', [$this, '_filter_remove_and_recurse_and_add2'], 11);

        $value .= '-' . $this->hook->apply_filters('', []) . '-';

        $this->hook->add_filter('remove_and_add', [$this, '_filter_remove_and_recurse_and_add2'], 11, 1);
        $this->check_priority_exists($this->hook, 11);
        return $value . '2';
    }

    public function _filter_remove_and_add3($value)
    {
        return $value . '3';
    }

    public function _filter_remove_and_add4($value)
    {
        return $value . '4';
    }

    public function test_remove_and_add_action()
    {
        $this->hook = new WP_Hook();

        $this->hook->add_filter('remove_and_add_action', '__return_empty_string', 10, 0);

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add2'], 11, 0);

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add4'], 12, 0);

        $this->hook->do_action([]);

        $this->assertSame('24', $this->action_output);
    }

    public function test_remove_and_add_last_action()
    {
        $this->hook = new WP_Hook();

        $this->hook->add_filter('remove_and_add_action', '__return_empty_string', 10, 0);

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add1'], 11, 0);

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add2'], 12, 0);

        $this->hook->do_action([]);

        $this->assertSame('12', $this->action_output);
    }

    public function test_remove_and_recurse_and_add_action()
    {
        $this->hook = new WP_Hook();

        $this->hook->add_filter('remove_and_add_action', '__return_empty_string', 10, 0);

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add1'], 11, 0);
        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_recurse_and_add2'], 11, 0);
        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add3'], 11, 0);

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add4'], 12, 0);

        $this->hook->do_action([]);

        $this->assertSame('1-134-234', $this->action_output);
    }

    public function _action_remove_and_add1()
    {
        $this->action_output .= 1;
    }

    public function _action_remove_and_add2()
    {
        $this->hook->remove_filter('remove_and_add_action', [$this, '_action_remove_and_add2'], 11);
        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_add2'], 11, 0);

        $this->action_output .= '2';
    }

    public function _action_remove_and_recurse_and_add2()
    {
        $this->hook->remove_filter('remove_and_add_action', [$this, '_action_remove_and_recurse_and_add2'], 11);

        $this->action_output .= '-';
        $this->hook->do_action([]);
        $this->action_output .= '-';

        $this->hook->add_filter('remove_and_add_action', [$this, '_action_remove_and_recurse_and_add2'], 11, 0);

        $this->action_output .= '2';
    }

    public function _action_remove_and_add3()
    {
        $this->action_output .= '3';
    }

    public function _action_remove_and_add4()
    {
        $this->action_output .= '4';
    }

    protected function check_priority_exists($hook, $priority)
    {
        $priorities = $this->get_priorities($hook);

        $this->assertContains($priority, $priorities);
    }

    protected function get_priorities($hook)
    {
        $reflection = new ReflectionClass($hook);
        $reflection_property = $reflection->getProperty('priorities');
        $reflection_property->setAccessible(true);

        return $reflection_property->getValue($hook);
    }
}
