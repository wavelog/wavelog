<div class="container">
<br>
    <h2><?php echo lang('export_cfd_header'); ?></h2>

    <div class="card">
        <div class="card-header">
            <?php echo lang('export_cfd_description'); ?>
        </div>

        <div class="alert alert-warning" role="alert">
            <?php echo lang('export_cfd_grisquare_warning'); ?>
        </div>

        <div class="card-body">

            <form class="form" action="<?php echo site_url('cfdexport/export'); ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="from"><?php echo lang('gen_from_date') . ": " ?></label>
                        <input name="from" id="from" type="date" class="form-control w-auto">
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="to"><?php echo lang('gen_to_date') . ": " ?></label>
                        <input name="to" id="to" type="date" class="form-control w-auto">
                    </div>
                </div>    
                <br>
                <button type="submit" class="btn btn-primary mb-2" value="Export"><?php echo lang('general_word_export'); ?></button>
            </form>
        </div>
    </div>
</div>
