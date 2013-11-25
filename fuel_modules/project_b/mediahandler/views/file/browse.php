<?php if (($min_width>0)||($min_height>0)) { ?>
	<p class="note">Only images suitable for this field are shown 
	<i>(at least <?php if ($min_width>0) { ?><?=$min_width?>px wide<?php } ?>
	<?php if (($min_width>0)&&($min_height>0)) { ?>, <?php } ?>
	<?php if ($min_height>0) { ?><?=$min_height?>px high<?php } ?>)</i></p>
<?php } ?>



<?php if ($files): ?>
	<?php echo Form::open(array('enctype'=>'multipart/form-data','id'=>'browse')); ?>
		<?php #echo Form::submit('submit', 'Add Media', array('class' => 'btn btn-primary')); ?>

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
				<?php foreach ($files as $file): ?>
					<tr>
						<td width="100"><?=(preg_match('/image/i', $file->mime)?\Imagecache\Get::image_tag($file->filename,"browseView"):"")?></td>
						<td><?php echo $file->name; ?></td>
						<?php if (0) { ?><td><?php echo $file->mime; ?></td><?php } ?>
						<td><?php echo \Num::format_bytes($file->size); ?></td>
						<td>
							<?php if (preg_match('/image/i', $file->mime)) { ?>
								<?=$file->width?> &times; <?=$file->height?>
							<?php } else { ?>

							<?php } ?>
						</td>
						<td width="70"><?php 
								if ($max>1) {
									echo \Form::checkbox('savethis[]',$file->id,NULL,array('data-name'=>$file->name,'data-mime'=>$file->mime,'data-cached-img'=>\Imagecache\Get::image($file->filename,"adminImgSelect"),'data-path'=>$file->path)); 
								}
								else {
									echo Form::button('button', 'Use', array('class' => 'btn btn-primary', 'data-id'=>$file->id, 'data-name'=>$file->name,'data-mime'=>$file->mime,'data-cached-img'=>\Imagecache\Get::image($file->filename,"adminImgSelect"),'data-path'=>$file->path)); 
								}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ($max>1) { ?>
			<div class="actions">
				<?php echo Form::button('button', 'Add Media', array('class' => 'btn btn-primary')); ?>
			</div>
		<?php } ?>

	<?php echo Form::close(); ?>
<?php else: ?>
	<p>No files found.</p>
<?php endif; ?>