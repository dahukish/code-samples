<?php echo Form::open(array('enctype'=>'multipart/form-data',)); ?>
	<fieldset>
		<div class="clearfix">
			<?php echo Form::label('Upload', 'file_upload'); ?>
			<div class="input">
				<?php echo Form::file('file_upload', Input::post('file_upload', array('class' => 'span4'))); ?>
			</div>
		</div>
		<div class="actions">
			<?php echo Form::submit('submit', 'Upload', array('class' => 'btn btn-primary')); ?>
		</div>
	</fieldset>
<?php echo Form::close(); ?>