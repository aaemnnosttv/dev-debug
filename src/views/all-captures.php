<?php
/**
 * @var Capture[] $captures
 */

use DevDebug\Capture;
?>
<div id="dev_debug_captures">
    <?php
    foreach ( $captures as $capture ) {
        $formatter = $capture->formatter();
        $uid = $formatter->uid;
        include __DIR__ . '/capture.php';
    }
    ?>
</div>
