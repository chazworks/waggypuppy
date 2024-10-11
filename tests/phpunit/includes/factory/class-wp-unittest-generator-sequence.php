<?php

class WP_UnitTest_Generator_Sequence
{
    public static $incr = -1;
    public $next;
    public $template_string;

    public function __construct($template_string = '%s', $start = null)
    {
        if ($start) {
            $this->next = $start;
        } else {
            ++self::$incr;
            $this->next = self::$incr;
        }
        $this->template_string = $template_string;
    }

    public function next()
    {
        $generated = sprintf($this->template_string, $this->next);
        ++$this->next;
        return $generated;
    }

    /**
     * Get the incrementor.
     *
     * @return int
     * @since 4.6.0
     *
     */
    public function get_incr()
    {
        return self::$incr;
    }

    /**
     * Get the template string.
     *
     * @return string
     * @since 4.6.0
     *
     */
    public function get_template_string()
    {
        return $this->template_string;
    }
}
