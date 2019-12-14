<?php

namespace DevDebug\Formatter;

use DevDebug;
use DevDebug\Capture;

/**
 * @property-read string $type
 */
abstract class BaseFormatter
{
    /**
     * @var Capture
     */
    protected $capture;

    /**
     * @return string
     */
    abstract public function get_type();

    /**
     * @return string
     */
    abstract public function get_panel_content();

    /**
     * @return string
     */
    abstract public function get_type_label();

    public function __construct(Capture $capture)
    {
        $this->capture = $capture;
    }

    public function render_tab($panel_id)
    {
        ?>
        <li class="ddcapture-tab">
            <label for="<?= esc_attr($panel_id) ?>">
                <?= esc_html($this->get_type_label()) ?>
            </label>
        </li>
        <?php
    }

    public function __get($name)
    {
        switch ($name) {
            case 'type':
                return $this->get_type();
        }

        return null;
    }
}
