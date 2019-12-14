<?php

namespace DevDebug\Formatter;

class PrintRFormatter extends BaseFormatter
{

    /**
     * @inheritDoc
     */
    public function get_type()
    {
        return 'printr';
    }

    /**
     * @inheritDoc
     */
    public function get_type_label()
    {
        return 'print_r';
    }

    public function get_panel_content()
    {
        return esc_html(print_r($this->capture->data, true));
    }
}
