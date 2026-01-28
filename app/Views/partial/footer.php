<?php

use Config\OSPOS;

?>

        </div>
    </div>

    <div id="footer">
        <div class="jumbotron push-spaces">
            <strong>
                <?= lang('Common.copyrights', [date('Y')]) ?> ·
                dot.co ·
                <?= esc(config('App')->application_version) ?>
            </strong>.
        </div>
    </div>
</body>

</html>
