<?php

namespace DevDebug\Formatter;

class VarDumpFormatter extends BaseFormatter
{
    /**
     * @inheritDoc
     */
    public function get_type()
    {
        return 'vardump';
    }

    /**
     * @inheritDoc
     */
    public function get_type_label()
    {
        return 'var_dump';
    }

    public function get_panel_content()
    {
        if (extension_loaded('xdebug')) {
            return $this->xdebug_panel_html();
        } else {
            return esc_html($this->capture_dump_output());
        }
    }

    protected function xdebug_panel_html()
    {
        $max_depth = ini_get('xdebug.var_display_max_depth');
        $max_children = ini_get('xdebug.var_display_max_children');
        $max_data = ini_get('xdebug.var_display_max_data');

        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);

        $html = $this->capture_dump_output();

        ini_set('xdebug.var_display_max_depth', $max_depth);
        ini_set('xdebug.var_display_max_children', $max_children);
        ini_set('xdebug.var_display_max_data', $max_data);

        return $html;
    }

    protected function capture_dump_output()
    {
        ob_start();
        var_dump($this->capture->data);

        return ob_get_clean();
    }
}
