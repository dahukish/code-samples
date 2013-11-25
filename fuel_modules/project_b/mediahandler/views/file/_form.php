<?php echo Form::open(); ?>

	<fieldset>
		<div class="clearfix">
			<?php echo Form::label('Name', 'name'); ?>

			<div class="input">
				<?php echo Form::input('name', Input::post('name', isset($file_file) ? $file_file->name : ''), array('class' => 'span4')); ?>

			</div>
		</div>
		<div class="clearfix">
			<?php echo Form::label('Slug', 'slug'); ?>

			<div class="input">
				<?php echo Form::input('slug', Input::post('slug', isset($file_file) ? $file_file->slug : ''), array('class' => 'span4')); ?>

			</div>
		</div>
		<div class="clearfix">
			<?php echo Form::label('Mime', 'mime'); ?>

			<div class="input">
				<?php echo Form::input('mime_dupe', Input::post('mime_dupe', isset($file_file) ? $file_file->mime : ''), array('class' => 'span4','disabled'=>'disabled')); ?>
				<?php echo Form::hidden('mime', Input::post('mime', isset($file_file) ? $file_file->mime : ''), array('class' => 'span4')); ?>

			</div>
		</div>
		<div class="clearfix">
			<?php echo Form::label('Path', 'path'); ?>

			<div class="input">
				<?php echo Form::input('path_dupe', Input::post('path_dupe', isset($file_file) ? $file_file->path : ''), array('class' => 'span4','disabled'=>'disabled')); ?>
				<?php echo Form::hidden('path', Input::post('path', isset($file_file) ? $file_file->path : ''), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<?php echo Form::label('Filename', 'filename'); ?>

			<div class="input">
				<?php echo Form::input('filename_dupe', Input::post('filename_dupe', isset($file_file) ? $file_file->filename : ''), array('class' => 'span4','disabled'=>'disabled')); ?>
				<?php echo Form::hidden('filename', Input::post('filename', isset($file_file) ? $file_file->filename : ''), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<?php echo Form::label('Saved As', 'saved_as'); ?>

			<div class="input">
				<?php echo Form::input('saved_as_dupe', Input::post('saved_as_dupe', isset($file_file) ? $file_file->saved_as : ''), array('class' => 'span4','disabled'=>'disabled')); ?>
				<?php echo Form::hidden('saved_as', Input::post('saved_as', isset($file_file) ? $file_file->saved_as : ''), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<?php echo Form::label('Extension', 'extension'); ?>

			<div class="input">
				<?php echo Form::input('extension_dupe', Input::post('extension_dupe', isset($file_file) ? $file_file->extension : ''), array('class' => 'span4','disabled'=>'disabled')); ?>
				<?php echo Form::hidden('extension', Input::post('extension', isset($file_file) ? $file_file->extension : ''), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<?php echo Form::label('Size', 'size'); ?>

			<div class="input">
				<?php echo Form::input('size_dupe', Input::post('size_dupe', isset($file_file) ? $file_file->size : ''), array('class' => 'span4','disabled'=>'disabled')); ?>
				<?php echo Form::hidden('size', Input::post('size', isset($file_file) ? $file_file->size : ''), array('class' => 'span4')); ?>

			</div>
		</div>
		<!-- <div class="clearfix">
			<?php #echo Form::label('Height', 'height'); ?>

			<div class="input">
				<?php #echo Form::input('height', Input::post('height', isset($file_file) ? $file_file->height : ''), array('class' => 'span4')); ?>

			</div>
		</div>
		<div class="clearfix">
			<?php #echo Form::label('Width', 'width'); ?>

			<div class="input">
				<?php #echo Form::input('width', Input::post('width', isset($file_file) ? $file_file->width : ''), array('class' => 'span4')); ?>

			</div>
		</div> -->

		<div class="clearfix">
			<?php echo Form::label('Alt Text', 'alt_text'); ?>

			<div class="input">
				<?php echo Form::input('alt_text', Input::post('alt_text', isset($file_file) ? $file_file->alt_text : ''), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<?php echo Form::label('External Url (used for offsite links only)', 'ext_url'); ?>

			<div class="input">
				<?php echo Form::input('ext_url', Input::post('ext_url', isset($file_file) ? $file_file->ext_url : ''), array('class' => 'span4')); ?>

			</div>
		</div>

		<div class="clearfix">
			<?php echo Form::label('Embed Code (please make sure video dimensions are 274x201 pixels in size)', 'embed_code'); ?>

			<div class="input">
				<?php echo Form::textarea('embed_code', Input::post('embed_code', isset($file_file) ? stripslashes($file_file->embed_code) : ''), array('rows'=>6,'cols'=>8)); ?>

			</div>
		</div>

		<div class="clearfix">
			<div class="input">
				<?php echo Form::hidden('author_id', Input::post('author_id', isset($file_file) ? $file_file->author_id : $current_user->id), array('class' => 'span4')); ?>

			</div>
		</div>		

		<div class="actions">
			<?php echo Form::submit('submit', 'Save', array('class' => 'btn btn-primary')); ?>

		</div>
	</fieldset>
<?php echo Form::close(); ?>