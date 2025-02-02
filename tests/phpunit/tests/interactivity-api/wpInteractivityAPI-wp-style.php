<?php

/**
 * Unit tests covering the data_wp_style_processor functionality of the
 * WP_Interactivity_API class.
 *
 * @package WP
 * @subpackage Interactivity API
 *
 * @since 6.5.0
 *
 * @coversDefaultClass WP_Interactivity_API
 *
 * @group interactivity-api
 */
class Tests_WP_Interactivity_API_WP_Style extends WP_UnitTestCase
{
    /**
     * Instance of WP_Interactivity_API.
     *
     * @var WP_Interactivity_API
     */
    protected $interactivity;

    /**
     * Set up.
     */
    public function set_up()
    {
        parent::set_up();
        $this->interactivity = new WP_Interactivity_API();
        $this->interactivity->state(
            'myPlugin',
            [
                'green' => 'green',
                'false' => false,
            ],
        );
    }

    /**
     * Invokes the private `merge_style_property` method of WP_Interactivity_API
     * class.
     *
     * @param string $style_attribute_value The current style attribute value.
     * @param string $style_property_name The style property name to set.
     * @param string|false|null $style_property_value The value to set for the style property. With false, null or an
     *                                                 empty string, it removes the style property.
     * @return string The new style attribute value after the specified property has been added, updated or removed.
     */
    private function merge_style_property($style_attribute_value, $style_property_name, $style_property_value)
    {
        $evaluate = new ReflectionMethod($this->interactivity, 'merge_style_property');
        $evaluate->setAccessible(true);
        return $evaluate->invokeArgs($this->interactivity,
            [$style_attribute_value, $style_property_name, $style_property_value]);
    }

    /**
     * Tests that `merge_style_property` correctly sets style properties.
     *
     * @ticket 60356
     *
     * @covers ::merge_style_property
     */
    public function test_merge_style_property_sets_properties()
    {
        // Adds property on empty style attribute.
        $result = $this->merge_style_property('', 'color', 'green');
        $this->assertSame('color:green;', $result);

        // Changes style property when there is an existing property.
        $result = $this->merge_style_property('color:red;', 'color', 'green');
        $this->assertSame('color:green;', $result);

        // Adds a new property when the existing one does not match.
        $result = $this->merge_style_property('color:red;', 'background', 'blue');
        $this->assertSame('color:red;background:blue;', $result);

        // Handles multiple existing properties.
        $result = $this->merge_style_property('color:red;margin:5px;', 'color', 'green');
        $this->assertSame('margin:5px;color:green;', $result);

        // Adds a new property when multiple existing properties do not match.
        $result = $this->merge_style_property('color:red;margin:5px;', 'padding', '10px');
        $this->assertSame('color:red;margin:5px;padding:10px;', $result);

        // Removes whitespaces in all properties.
        $result = $this->merge_style_property(' color : red; margin : 5px; ', 'padding', ' 10px ');
        $this->assertSame('color:red;margin:5px;padding:10px;', $result);

        // Updates a property when it's not the first one in the value.
        $result = $this->merge_style_property('color:red;margin:5px;', 'margin', '15px');
        $this->assertSame('color:red;margin:15px;', $result);

        // Adds missing trailing semicolon.
        $result = $this->merge_style_property('color:red;margin:5px', 'padding', '10px');
        $this->assertSame('color:red;margin:5px;padding:10px;', $result);

        // Doesn't add double semicolons.
        $result = $this->merge_style_property('color:red;margin:5px;', 'padding', '10px;');
        $this->assertSame('color:red;margin:5px;padding:10px;', $result);

        // Handles empty properties in the input.
        $result = $this->merge_style_property('color:red;;margin:5px;;', 'padding', '10px');
        $this->assertSame('color:red;margin:5px;padding:10px;', $result);

        // Moves the modified property to the end.
        $result = $this->merge_style_property('border-style: dashed; border: 3px solid red;', 'border-style', 'inset');
        $this->assertSame('border:3px solid red;border-style:inset;', $result);
    }

    /**
     * Tests that `merge_style_property` works correctly with falsy values,
     * removing or ignoring them as appropriate.
     *
     * @ticket 60356
     *
     * @covers ::merge_style_property
     */
    public function test_merge_style_property_with_falsy_values()
    {
        // Removes a property with an empty string.
        $result = $this->merge_style_property('color:red;margin:5px;', 'color', '');
        $this->assertSame('margin:5px;', $result);

        // Removes a property with null.
        $result = $this->merge_style_property('color:red;margin:5px;', 'color', null);
        $this->assertSame('margin:5px;', $result);

        // Removes a property with false.
        $result = $this->merge_style_property('color:red;margin:5px;', 'color', false);
        $this->assertSame('margin:5px;', $result);

        // Removes a property with 0.
        $result = $this->merge_style_property('color:red;margin:5px;', 'color', 0);
        $this->assertSame('margin:5px;', $result);

        // It doesn't add a new property with an empty string.
        $result = $this->merge_style_property('color:red;', 'padding', '');
        $this->assertSame('color:red;', $result);

        // It doesn't add a new property with null.
        $result = $this->merge_style_property('color:red;', 'padding', null);
        $this->assertSame('color:red;', $result);

        // It doesn't add a new property with false.
        $result = $this->merge_style_property('color:red;', 'padding', false);
        $this->assertSame('color:red;', $result);

        // It doesn't add a new property with 0.
        $result = $this->merge_style_property('color:red;', 'padding', 0);
        $this->assertSame('color:red;', $result);
    }

    /**
     * Invokes the `process_directives` method of WP_Interactivity_API class.
     *
     * @param string $html The HTML that needs to be processed.
     * @return array An array containing an instance of the WP_HTML_Tag_Processor and the processed HTML.
     */
    private function process_directives($html)
    {
        $new_html = $this->interactivity->process_directives($html);
        $p = new WP_HTML_Tag_Processor($new_html);
        $p->next_tag();
        return [$p, $new_html];
    }

    /**
     * Tests that the `data-wp-style` directive sets a style attribute with
     * correct property and value.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_sets_style_attribute()
    {
        $html = '<div data-wp-style--color="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive applies multiple style properties
     * correctly.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_sets_multiple_style_properties()
    {
        $html = '
			<div
				data-wp-style--color="myPlugin::state.green"
				data-wp-style--background="myPlugin::state.green"
			>Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;background:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive correctly handles different style
     * property values.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_sets_multiple_style_properties_with_different_values()
    {
        $html = '
			<div
				data-wp-style--color="myPlugin::state.green"
				data-wp-style--background="myPlugin::state.false"
			>Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;', $p->get_attribute('style'));

        $html = '
			<div
				style="background:red;"
				data-wp-style--color="myPlugin::state.green"
				data-wp-style--background="myPlugin::state.false"
			>Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive sets a new style property when
     * another already exists.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_sets_style_property_when_style_attribute_exists()
    {
        $html = '<div style="padding:10px;" data-wp-style--color="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive overwrites an existing style
     * property with a new value.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_overwrites_style_property_when_style_property_exists()
    {
        $html = '<div style="color:red;" data-wp-style--color="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive doesn't add a style property when
     * the directive value is false.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_doesnt_add_style_attribute_on_false()
    {
        $html = '<div data-wp-style--color="myPlugin::state.false">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive doesn't modify existing style
     * properties when directive value is false.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_doesnt_add_style_property_on_false()
    {
        $html = '<div style="padding:10px;" data-wp-style--color="myPlugin::state.false">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive keeps an existing style property
     * with a matching value.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_keeps_style_property_when_style_property_exists()
    {
        $html = '<div style="color:green;" data-wp-style--color="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive maintains style properties even
     * when they aren't the only ones present.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_keeps_style_property_when_style_property_exists_and_is_not_the_only_one()
    {
        $html = '<div style="padding:10px;color:green;" data-wp-style--color="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive removes the style attribute when
     * it contains only one property which is being removed.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_removes_style_attribute_when_style_property_exists_and_is_the_only_one()
    {
        $html = '<div style="color:green;" data-wp-style--color="myPlugin::state.false">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive removes a style property when it's
     * not the only one present and the directive value is false.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_removes_style_property_when_style_property_exists_and_is_not_the_only_one()
    {
        $html = '<div style="padding:10px;color:green;" data-wp-style--color="myPlugin::state.false">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive does not remove an empty style
     * attribute.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_doesnt_remove_empty_style_attribute()
    {
        $html = '<div style data-wp-style--color="myPlugin::state.false">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertTrue($p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive does not change the style
     * attribute when the directive suffix is empty.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_doesnt_change_style_attribute_with_empty_directive_suffix()
    {
        $html = '<div style="padding:10px;" data-wp-style="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive does not change the style
     * attribute when the value of the directive is empty.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     * @expectedIncorrectUsage WP_Interactivity_API::evaluate
     */
    public function test_wp_style_doesnt_change_style_attribute_with_empty_value()
    {
        $html = '<div style="padding:10px" data-wp-style--color="">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive doesn't apply changes if no value
     * is provided for the style property.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     * @expectedIncorrectUsage WP_Interactivity_API::evaluate
     */
    public function test_wp_style_doesnt_change_style_attribute_without_value()
    {
        $html = '<div style="padding: 10px;" data-wp-style--color>Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('padding:10px;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive functions correctly with multiple
     * identical directives.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_works_with_multiple_directives()
    {
        $html = '<div data-wp-style--color="myPlugin::state.green" data-wp-style--color="myPlugin::state.green">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertSame('color:green;', $p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive doesn't apply any changes when the
     * state value is true.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_doesnt_do_anything_on_true_values()
    {
        $this->interactivity->state('myPlugin', ['true' => true]);
        $html = '<div data-wp-style--color="myPlugin::state.text">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));
    }

    /**
     * Tests that the `data-wp-style` directive doesn't add a style property for
     * various falsy values in the state.
     *
     * @ticket 60356
     *
     * @covers ::process_directives
     */
    public function test_wp_style_doesnt_add_style_property_on_falsy_values()
    {
        $this->interactivity->state('myPlugin', ['text' => '']);
        $html = '<div data-wp-style--color="myPlugin::state.text">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));

        $this->interactivity->state('myPlugin', ['array' => []]);
        $html = '<div data-wp-style--color="myPlugin::state.array">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));

        $this->interactivity->state('myPlugin', ['number' => 0]);
        $html = '<div data-wp-style--color="myPlugin::state.number">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));

        $this->interactivity->state('myPlugin', ['null' => null]);
        $html = '<div data-wp-style--color="myPlugin::state.null">Text</div>';
        [$p] = $this->process_directives($html);
        $this->assertNull($p->get_attribute('style'));
    }
}
