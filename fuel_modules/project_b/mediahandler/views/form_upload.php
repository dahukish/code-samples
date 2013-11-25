<?php 
		echo Asset::css(array(
		'bootstrap.css',
		'http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js',
		'overcast/jquery-ui-1.9.2.custom.min.css',
		'select2.css',
		'spectrum.css',
		'jquery.ptTimeSelect.css',
		'bds/bds_admin_styles.css',
		)); 
	?>
<?php echo Form::open(array('enctype'=>'multipart/form-data','id'=>'mediahandler_upload','action'=>'/mediahandler/handler/ajax_upload')); ?>
<?php if (Session::get_flash('success')): ?>
				<div class="alert alert-success">
					<button class="close" data-dismiss="alert">×</button>
					<p><?php echo implode('</p><p>', (array) Session::get_flash('success')); ?></p>
				</div>
<?php endif; ?>
<?php if (Session::get_flash('error')): ?>
				<div class="alert alert-error">
					<button class="close" data-dismiss="alert">×</button>
					<p><?php echo implode('</p><p>', (array) Session::get_flash('error')); ?></p>
				</div>
<?php endif; ?>
	<fieldset>
		<div class="clearfix">
			<?php echo Form::label('Upload', 'file_upload'); ?>
			<div class="input">
				<?php echo Form::file('file_upload', array('class' => 'span4')); ?>
				<?php echo Form::hidden('file', \Input::post('file',(isset($old_file))? $old_file : ''), array()); ?>
				<?php echo Form::hidden('image', $image, array()); ?>
				<?php echo Form::hidden('fld', $fld, array()); ?>
			</div>
		</div>
		<div class="actions">
			<?php echo Form::submit('submit', 'Upload', array('class' => 'btn btn-primary')); ?>
		</div>
	</fieldset>
<?php echo Form::close(); ?>