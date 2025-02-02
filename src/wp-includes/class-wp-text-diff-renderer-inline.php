<?php
/**
 * Diff API: WP_Text_Diff_Renderer_inline class
 *
 * @package WP
 * @subpackage Diff
 * @since 4.7.0
 */

/**
 * Better word splitting than the PEAR package provides.
 *
 * @since 2.6.0
 * @uses Text_Diff_Renderer_inline Extends
 */
#[AllowDynamicProperties]
class WP_Text_Diff_Renderer_inline extends Text_Diff_Renderer_inline
{

    /**
     * @param string $string
     * @param string $newlineEscape
     * @return string
     * @since 2.6.0
     *
     * @ignore
     */
    public function _splitOnWords($string, $newlineEscape = "\n")
    {
        $string = str_replace("\0", '', $string);
        $words = preg_split('/([^\w])/u', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $words = str_replace("\n", $newlineEscape, $words);
        return $words;
    }
}
