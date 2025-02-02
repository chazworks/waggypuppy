<?php

class WP_UnitTest_Factory_Callback_After_Create
{

    /**
     * @var callable
     */
    public $callback;

    /**
     * WP_UnitTest_Factory_Callback_After_Create constructor.
     *
     * @param callable $callback A callback function.
     * @since UT (3.7.0)
     *
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Calls the set callback on a given object.
     *
     * @param int $object_id ID of the object to apply the callback on.
     *
     * @return mixed Updated object field.
     * @since UT (3.7.0)
     *
     */
    public function call($object_id)
    {
        return call_user_func($this->callback, $object_id);
    }
}
