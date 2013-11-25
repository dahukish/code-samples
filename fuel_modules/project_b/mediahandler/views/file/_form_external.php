<?php echo Form::open(array('id'=>'external')); ?>

	<fieldset>
		<div class="clearfix">
			<?php echo Form::label('Name', 'name'); ?>

			<div class="input">
				<?php echo Form::input('name', '', array('class' => 'span4')); ?>

			</div>
		</div>
		<div class="clearfix">
			<?php echo Form::label('External Link', 'ext_url'); ?>

			<div class="input">
				<?php echo Form::input('ext_url', '', array('class' => 'span4')); ?>

			</div>
		</div>
		<div class="clearfix">
			<?php echo Form::label('Embed Code', 'embed_code'); ?>

			<div class="input">
				<?php echo Form::textarea('embed_code', '', array('class' => 'span4', 'rows'=> 6, 'cols'=>8)); ?>

			</div>
		</div>
		<div class="clearfix">

				<?php echo Form::hidden('slug','', array('class' => 'span4')); ?>
				<?php echo Form::hidden('mime', 'external/data', array('class' => 'span4')); ?>
				<?php echo Form::hidden('path', '', array('class' => 'span4')); ?>
				<?php echo Form::hidden('filename', '', array('class' => 'span4')); ?>
				<?php echo Form::hidden('saved_as', '', array('class' => 'span4')); ?>
				<?php echo Form::hidden('extension', '', array('class' => 'span4')); ?>
				<?php echo Form::hidden('size', 0, array('class' => 'span4')); ?>


		</div>
		<div class="clearfix"?>
			<?php echo Form::label('Height', 'height'); ?>
			<div class="input">
				<?php echo Form::input('height', 0, array('class' => 'span4')); ?>
			</div>
		</div>

		<div class="clearfix"?>
			<?php echo Form::label('Width', 'width'); ?>
			<div class="input">
				<?php echo Form::input('width', 0, array('class' => 'span4')); ?>
			</div>
		</div>
		<div class="clearfix">
			<?php echo Form::label('Alt Text', 'alt_text'); ?>

			<div class="input">
				<?php echo Form::input('alt_text', '', array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<div class="input">
				<?php echo Form::hidden('author_id', \Input::post('author_id', isset($file_file) ? $file_file->author_id : $current_user->id), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="actions">
			<?php echo \Form::submit('submit', 'Save', array('class' => 'btn btn-primary')); ?>

		</div>
	</fieldset>
<?php echo \Form::close(); ?>