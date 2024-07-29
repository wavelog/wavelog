<div class="container">
<br>
    <h2>CFD</h2>

    <div class="card">
        <div class="card-header">
            <?= sprintf(__("Export of CFD-File for DARC-Toplist (See %s)"), "<a href='https://dcl.darc.de/toplist' target='_blank'>https://dcl.darc.de/toplist</a>"); ?>
        </div>

        <div class="card-body">

            <form class="form" action="<?php echo site_url('cfdexport/export'); ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="from"><?= __("From date") . ": " ?></label>
                        <input name="from" id="from" type="date" class="form-control w-auto">
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="to"><?= __("To date") . ": " ?></label>
                        <input name="to" id="to" type="date" class="form-control w-auto">
                    </div>
                </div>    
                <br>
                <button type="submit" class="btn btn-primary mb-2" value="Export"><?= __("Export"); ?></button>
            </form>
        </div>
    </div>
</div>
