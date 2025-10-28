<h1><?= $page_title ?></h1>

<?php if (!empty($locations)): ?>
    <table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
        <thead>
            <tr>
                <?php
                // Dynamically create table headers based on object keys
                $first_row = (array)$locations[0];
                foreach (array_keys($first_row) as $key): ?>
                    <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $loc): ?>
                <tr>
                    <?php foreach ((array)$loc as $val): ?>
                        <td><?php echo $val; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="no-data">No locations found.</div>
<?php endif; ?>
