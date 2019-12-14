<?php

namespace DevDebug;

use DevDebug\Formatter\CaptureFormatter;

/**
 * @property-read mixed $data
 * @property-read mixed $title
 */
class Capture
{
    /**
     * @var array
     */
    public $args;

    /**
     * Capture constructor.
     *
     * @param array $args
     */
    public function __construct($args)
    {
        $this->args = $args;
    }

    public function formatter()
    {
        return new CaptureFormatter($this);
    }

    public function __get($name)
    {
        if (isset($this->args[$name])) {
            return $this->args[$name];
        }

        return null;
    }
}
