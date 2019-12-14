<?php

namespace DevDebug\Formatter;

use DevDebug;

class BacktraceFormatter
{
    private $trace;

    /**
     * BacktraceFormatter constructor.
     *
     * @param array $trace
     */
    public function __construct($trace)
    {
        $this->trace = $trace;
    }

    /**
     * Return a backtrace as an html formatted list
     *
     * @return string
     */
    public function get_html()
    {
        $d = [
            'func' => '',
            'file' => '',
            'line' => '',
            'args' => [],
        ];
        $list = [];

        foreach ($this->prepare_backtrace() as $key => $t) {
            $t = array_merge($d, $t);

            $lineclass = ($key % 2) ? 'even' : 'odd';
            $args_html = ! empty($t['args'])
                ? sprintf(' <span class="args">%s</span> ', implode(', ', $t['args']))
                : '';

            $filemeta = $t['file']
                ? "<span class='file'>{$t['file']}</span><span class='line'>:{$t['line']}</span>"
                : '';

            $line = <<<HTML
			<div class="trace $lineclass">
				<div class="called">{$t['func']}($args_html)</div>
				<div class='filemeta'>$filemeta</div>
			</div>
HTML;

            $list[] = $line;
        }

        return implode($list);
    }

    protected function prepare_backtrace()
    {
        $f = [];

        foreach ($this->trace as $i => $data) {
            if (isset($data['file']) && preg_match('/wp-settings\.php$/', $data['file'])) {
                break;
            } // don't include this stuff that will be on every function..

            // arguments
            if (! empty($data['args'])) {
                $f[$i]['args'] = $this->format_args($data['args'], true);
            }

            // file & line
            foreach (['file', 'line'] as $key) {
                if (isset($data[$key])) {
                    $f[$i][$key] = $data[$key];
                }
            }

            // function
            if (isset($data['class'])) {
                $f[$i]['func'] = "<span class='class'>{$data['class']}</span>";
                $f[$i]['func'] .= "<span class='call-type'>{$data['type']}</span>";
                $f[$i]['func'] .= "<span class='method'>{$data['function']}</span>";
            } else {
                $f[$i]['func'] = "<span class='function'>{$data['function']}</span>";
            }
        }

        return $f;
    }


    function format_args($args, $recurse = false)
    {
        $new = [];
        foreach ($args as $i => $a) {
            if (is_string($a)) {
                $n = sprintf("'%s'", esc_html($a));
            } elseif (is_array($a)) {
                if (empty($a)) {
                    $n = 'array()';
                } // class/object callback
                elseif (
                    2 === count($a)
                    && (isset($a[0]) && isset($a[1]))
                    && (is_object($a[0]) || (is_string($a[0]) && class_exists($a[0])))
                    && (is_string($a[1]) && method_exists($a[0], $a[1]))
                ) {
                    $ob = is_object($a[0])
                        ? sprintf('<span class="instance class">%s</span>', get_class($a[0]))
                        : sprintf('<span class="class-name">%s</span>', "'{$a[0]}'");

                    $n = sprintf('array( %s, \'%s\' )', $ob, $a[1]);
                } else {
                    if ($recurse && count($a) < 10) {
                        $n = 'array(' . join(',', $this->format_args($a)) . ')';
                    } else {
                        $n = 'array(::' . count($a) . '::)';
                    }
                }
            } elseif (is_object($a)) {
                $n = sprintf('<span class="instance class">%s</span>', get_class($a));
            } elseif (is_bool($a)) {
                $n = sprintf('<span class="boolean %1$s">%1$s</span>', $a ? 'true' : 'false');
            } elseif (is_numeric($a)) {
                $n = $a;
            } elseif (is_null($a)) {
                $n = "<span class='null'>NULL</span>";
            } else {
                $n = $a;
                DevDebug::get_instance()->log('Missing type _' . gettype($a) . '_ handling!', __METHOD__);
            }

            $new[$i] = "<span class=\"arg\">$n</span>";
        }

        return array_filter($new);
    }
}
