<h2>Editing File</h2>
<br>

<?php echo render('file/_form'); ?>
<p>
	<?php echo Html::anchor('mediahandler/file/view/'.$file_file->id, 'View'); ?> |
	<?php echo Html::anchor('mediahandler/file', 'Back'); ?></p>
