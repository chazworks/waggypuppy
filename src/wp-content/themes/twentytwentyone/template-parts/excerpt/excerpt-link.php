<?php
/**
 * Show the appropriate content for the Link post format.
 *
 * @link https://developer.wp.org/themes/basics/template-hierarchy/
 *
 * @package WP
 * @subpackage Twenty_Twenty_One
 * @since Twenty Twenty-One 1.0
 */

// Print the 1st instance of a paragraph block. If none is found, print the content.
if (has_block('core/paragraph', get_the_content())) {
    twenty_twenty_one_print_first_instance_of_block('core/paragraph', get_the_content());
} else {
    the_content();
}
