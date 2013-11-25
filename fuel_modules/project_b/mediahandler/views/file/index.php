<h2>Listing Media</h2>
<br>
<?php if ($file_files): ?>
<table class="table table-striped">
	<thead>
		<tr>
			<th>Name</th>
			<th>Slug</th>
			<th>Mime</th>
			<th>Path</th>
			<th>Size</th>
			<th>Height</th>
			<th>Width</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php foreach ($file_files as $file_file): ?>		<tr>

			<td><?php echo $file_file->name; ?></td>
			<td><?php echo $file_file->slug; ?></td>
			<td><?php echo $file_file->mime; ?></td>
			<td><?php echo $file_file->path; ?></td>
			<td><?php echo $file_file->size; ?></td>
			<td><?php echo $file_file->height; ?></td>
			<td><?php echo $file_file->width; ?></td>
			<td>
				<?php echo Html::anchor('mediahandler/file/view/'.$file_file->id, 'View'); ?> |
				<?php echo Html::anchor('mediahandler/file/edit/'.$file_file->id, 'Edit'); ?> |
				<?php echo Html::anchor('mediahandler/file/delete/'.$file_file->id, 'Delete', array('onclick' => "return confirm('Are you sure?')")); ?>

			</td>
		</tr>
<?php endforeach; ?>	</tbody>
</table>

<?php else: ?>
<p>No Media.</p>

<?php endif; ?><p>
	<?php echo Html::anchor('mediahandler/file/create', 'Add new File', array('class' => 'btn btn-success')); ?>

</p>
