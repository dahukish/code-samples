<h2>Viewing #<?php echo $file_file->id; ?></h2>

<p>
	<strong>Name:</strong>
	<?php echo $file_file->name; ?></p>
<p>
	<strong>Slug:</strong>
	<?php echo $file_file->slug; ?></p>
<p>
	<strong>Mime:</strong>
	<?php echo $file_file->mime; ?></p>
<p>
	<strong>Path:</strong>
	<?php echo $file_file->path; ?></p>
<p>
	<strong>Size:</strong>
	<?php echo $file_file->size; ?></p>
<p>
	<strong>Height:</strong>
	<?php echo $file_file->height; ?></p>
<p>
	<strong>Width:</strong>
	<?php echo $file_file->width; ?></p>

<?php echo Html::anchor('mediahandler/file/edit/'.$file_file->id, 'Edit'); ?> |
<?php echo Html::anchor('mediahandler/file', 'Back'); ?>