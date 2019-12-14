<?php

namespace DevDebug;

class Capture
{
    /**
     * @var string
     */
    public $uid;

    public $args;

    /**
     * Capture constructor.
     *
     * @param array $args
     */
    public function __construct($args)
    {
        $this->uid = uniqid();
        $this->args = $args;
    }

    public static function fromArray(array $args)
    {
        return new static($args);
    }
}
