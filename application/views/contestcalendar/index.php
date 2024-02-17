<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://www.contestcalendar.com/">https://www.contestcalendar.com/</a></p>

		<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed dxcalendar">
			<thead>
				<tr>
					<th>Contest</th>
					<th>Date</th>
					<th>Link</th>
				</tr>
			</thead>
			<tbody>
			<?php
		foreach($rss->channel->item as $item) {
			echo '<tr>';
			$title = (string) $item->title;
			$link = (string) $item->link;
			$description = (string) $item->description;

			echo "<td>$title</td>";
			echo "<td>$description</td>";
			echo "<td><a href='$link' target=_blank>$link</a></td>";

			echo '</tr>';
		}
		?>
		</tbody>
	</table>

</div>
