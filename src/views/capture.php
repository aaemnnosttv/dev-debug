<?php
/**
 * @var Capture $capture
 * @var CaptureFormatter $formatter
 * @var int $uid Capture UID
 */

use DevDebug\Capture;
use DevDebug\Formatter\CaptureFormatter;
?>
<div class="ddprint">
    <div class="title">
        <?= esc_html($capture->title) ?>
        <span class="toggles">
            <label class="toggle-meta" for="meta_<?= esc_attr($uid) ?>">
                <svg role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"></path></svg>
            </label>
        </span>
    </div>

    <input type="checkbox" id="meta_<?= esc_attr($uid) ?>" data-meta-display style="display:none;" />
    <div class="meta" style="display:none;">
        <div class="backtrace">
            <?php $formatter->render_backtrace() ?>
        </div>
    </div>

    <div class="output">
        <div class="output-tabs">
            <ul class='dump-tabs'>
                <?php $formatter->render_tabs() ?>
            </ul>
        </div>
        <div class="panels">
            <?php $formatter->render_panels() ?>
        </div>
        <div style="clear:both;"></div>
    </div>
</div>
