<h3>All Media</h3>

<?php if ($files): ?>
	<?php echo Form::open(array('enctype'=>'multipart/form-data','id'=>'browse')); ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>Name</th>
					<!--<th>Type</th>-->
					<th>Size</th>
					<th>Dimensions</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
			<?php $lastType=""; ?>
				<?php foreach ($files as $file): ?>
				<?php 
						$docType=preg_match("/(jpe?g|bmp|gif|png)$/",$file->mime)?"Image":"Documents";
						if ($docType!=$lastType) { 
				?>
					<tr>
						<td colspan="5" style="background:#fff; font-size:10px; text-transform:uppercase;">&nbsp;<br /><b><?=$docType?></b></td>
					</tr>

				<?php		$lastType=$docType;
						} 
				?>
					<tr>
						<td width="100"><?php if (preg_match('/image/i', $file->mime)) { ?>
							<?=\Imagecache\Get::image_tag($file->filename,"browseView")?>
						<?php } else { ?>

						<?php } ?>
						</td>

						<td><?php echo $file->name; ?></td>

						<?php if (0) { ?><td><?php echo $file->mime; ?></td><?php } ?>
						<td><?php echo \Num::format_bytes($file->size); ?></td>
						<td>
							<?php if (preg_match('/image/i', $file->mime)) { ?>
								<?=$file->width?> &times; <?=$file->height?>
							<?php } else { ?>

							<?php } ?>
						<td width="100">
							<div class="btn-group">
								<?php echo Html::anchor('mediahandler/file/edit/'.$file->id, 'Edit', array('class' => 'btn btn-small')); ?>
								<?php echo Html::anchor('mediahandler/file/delete/'.$file->id, 'Delete', array('onclick' => "return confirm('Are you sure?')", 'class' => 'btn btn-small')); ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php echo Form::close(); ?>
<?php else: ?>
	<p>No files found.</p>
<?php endif; ?>