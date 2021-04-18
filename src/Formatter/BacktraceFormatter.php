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
        return array_map(
            function ($frame) {
                // arguments
                if (! empty($frame['args'])) {
                    $frame['args'] = $this->format_args($frame['args'], true);
                }

                // file & line
//                foreach (['file', 'line'] as $key) {
//                    if (isset($frame[$key])) {
//                        $f[$i][$key] = $frame[$key];
//                    }
//                }

                // function
                if (isset($frame['class'])) {
                    $frame['func'] = "<span class='class'>{$frame['class']}</span>"
                        . "<span class='call-type'>{$frame['type']}</span>"
                        . "<span class='method'>{$frame['function']}</span>";
                } else {
                    $frame['func'] = "<span class='function'>{$frame['function']}</span>";
                }

                return $frame;
            },
            $this->trace
        );
    }


    function format_args($args, $recurse = false)
    {
        if (! is_array($args)) {
            return $this->format_arg($args, $recurse);
        }

        return array_map(
            function ($arg) use ($recurse) {
                return $this->format_arg($arg, $recurse);
            },
            $args
        );
    }

    protected function format_arg($arg, $recurse)
    {
        if (is_string($arg)) {
            $n = sprintf("'%s'", esc_html($arg));
        } elseif (is_array($arg)) {
            if (empty($arg)) {
                $n = 'array()';
            } elseif ($recurse && count($arg) < 10) {
                $n = 'array(' . join(',', $this->format_args($arg, $recurse)) . ')';
            } else {
                $n = 'array(::' . count($arg) . '::)';
            }
        } elseif (is_object($arg)) {
            $n = get_class($arg);
        } elseif (is_bool($arg)) {
            $n = $arg ? 'true' : 'false';
        } elseif (is_null($arg)) {
            $n = 'NULL';
        } else {
            $n = $arg;
        }

        return sprintf(
            '<span class="arg %s">%s</span>',
            join(' ', $this->get_classes_for_value($arg)),
            $n
        );
    }

    protected function get_classes_for_value($input)
    {
        $classes = array( 'type-' . strtolower(gettype($input)) );

        if (empty($input)) {
            $classes[] = 'empty';
        }

        if (is_object($input)) {
            $classes[] = 'instance';
        }

        if (is_string($input) && class_exists($input)) {
            $classes[] = 'class-name';
        }

        return $classes;
    }
}
