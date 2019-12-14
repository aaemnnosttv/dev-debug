<?php

namespace DevDebug\Formatter;

use DevDebug\Capture;

/**
 * @property-read int $uid
 */
class CaptureFormatter
{
    /**
     * @var Capture
     */
    protected $capture;

    /**
     * @var array
     */
    protected $formatters;

    protected static $_uid = 0;

    /**
     * @var int
     */
    protected $uid;

    public function __construct(Capture $capture)
    {
        $this->capture = $capture;
        $this->formatters = [
            new VarExportFormatter($capture),
            new VarDumpFormatter($capture),
            new PrintRFormatter($capture),
        ];
        $this->uid = ++self::$_uid;
    }

    public function render_tabs()
    {
        array_walk(
            $this->formatters,
            function (BaseFormatter $formatter) {
                $formatter->render_tab($this->get_panel_id($formatter));
            }
        );
    }

    public function render_panels()
    {
        array_walk(
            $this->formatters,
            function (BaseFormatter $formatter) {
                $this->render_panel($formatter);
            }
        );
    }

    protected function render_panel(BaseFormatter $formatter)
    {
        $this->render_panel_radio($formatter);
        ?>
        <pre
            class="dump-panel dump-panel-<?= esc_attr($formatter->type) ?>"
            style="display: none;"
        ><?= $formatter->get_panel_content() ?></pre>
        <?php
    }

    protected function render_panel_radio(BaseFormatter $formatter)
    {
        $panel_id = $this->get_panel_id($formatter);
        ?>
        <input
            type="radio"
            id="<?= esc_attr($panel_id); ?>"
            name="<?= esc_attr("ddcapture_{$this->uid}") ?>"
            style="display: none;"
            data-dump-panel-display
            <?php checked($formatter === $this->formatters[0]) ?>
        >
        <?php
    }

    public function render_backtrace()
    {
        echo ( new BacktraceFormatter($this->capture->args['backtrace']) )->get_html();
    }

    protected function get_panel_id(BaseFormatter $formatter)
    {
        return "ddcapture_{$this->uid}_{$formatter->type}";
    }

    public function __get($name)
    {
        switch ($name) {
            case 'uid':
                return $this->{$name};
        }

        return null;
    }
}
