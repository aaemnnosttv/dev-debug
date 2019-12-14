<?php

namespace DevDebug\Formatter;

class VarExportFormatter extends BaseFormatter
{

    /**
     * @inheritDoc
     */
    public function get_type()
    {
        return 'varexport';
    }

    /**
     * @inheritDoc
     */
    public function get_type_label()
    {
        return 'var_export';
    }

    public function get_panel_content()
    {
        // Prevent running var_export with recursive data to prevent errors.
        if ($this->data_has_recursion()) {
            return '*RECURSION*';
        }

        return esc_html(var_export($this->capture->data, true));
    }

    protected function data_has_recursion()
    {
        return false !== strpos(print_r($this->capture->data, true), '*RECURSION*');
    }
}
