<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Mediahandler</title>

	<?php
		$css = array();
		$css[] = '/assets/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css';
		$js = array();
		if($view === 'ajax') $js[] = 'http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js';
		$js[] = 'http://bp.yahooapis.com/2.4.21/browserplus-min.js';
		$js[] = '/assets/plupload/plupload.full.js';
		$js[] = '/assets/plupload/jquery.plupload.queue/jquery.plupload.queue.js';

		echo Asset::css($css);
		echo Asset::js($js);
	?>

	<!-- Load Queue widget CSS and jQuery -->
	<!--<style type="text/css">@import url(/assets/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css);</style>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script> -->

	<!-- Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now) -->
	<!--<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>-->

	<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
	<!--<script type="text/javascript" src="/assets/plupload/plupload.full.js"></script>-->
	<!--<script type="text/javascript" src="/assets/plupload/jquery.plupload.queue/jquery.plupload.queue.js"></script>-->

	<script>
		// Convert divs to queue widgets when the DOM is ready
		$(function() {
			$("#uploader").pluploadQueue({
				// General settings
				runtimes: 'gears,flash,silverlight,browserplus,html5',
				url: '/mediahandler/handler/plupload',
				max_file_size: '10mb',
				chunk_size: '1mb',
				unique_names: true,

				// Specify what files to browse for
				filters: [
					{title: "Image files", extensions: "jpg,gif,png"},
					{title: "Zip files", extensions: "zip"}
				],

				// Flash settings
				flash_swf_url '/assets/plupload/plupload.flash.swf',

				// Silverlight settings
				silverlight_xap_url: '/assets/plupload/plupload.silverlight.xap',


					// PreInit events, bound before any internal events
					preinit: {
						Init: function(up, info) {
							console.log('[Init]', 'Info:', info, 'Features:', up.features);
						},

						UploadFile: function(up, file) {
							console.log('[UploadFile]', file);
						}
					},

					// Post init events, bound after the internal events
					init: {
						Refresh: function(up) {
							// Called when upload shim is moved
							console.log('[Refresh]');
						},

						StateChanged: function(up) {
							// Called when the state of the queue is changed
							console.log('[StateChanged]', up.state == plupload.STARTED ? "STARTED" : "STOPPED");
						},

						QueueChanged: function(up) {
							// Called when the files in queue are changed by adding/removing files
							console.log('[QueueChanged]');
						},

						UploadProgress: function(up, file) {
							// Called while a file is being uploaded
							console.log('[UploadProgress]', 'File:', file, "Total:", up.total);
						},

						FilesAdded: function(up, files) {
							// Callced when files are added to queue
							console.log('[FilesAdded]');

							plupload.each(files, function(file) {
								console.log('  File:', file);
							});
						},

						FilesRemoved: function(up, files) {
							// Called when files where removed from queue
							console.log('[FilesRemoved]');

							plupload.each(files, function(file) {
								console.log('  File:', file);
							});
						},

						FileUploaded: function(up, file, info) {
							// Called when a file has finished uploading
							console.log('[FileUploaded] File:', file, "Info:", info);
						},

						ChunkUploaded: function(up, file, info) {
							// Called when a file chunk has finished uploading
							console.log('[ChunkUploaded] File:', file, "Info:", info);
						},

						Error: function(up, args) {
							// Called when a error has occured
							console.log('[error] ', args);
						}
					}
			});


			// Client side form validation
			$('form').submit(function(e) {
				var uploader = $('#uploader').pluploadQueue();

				// Files in queue upload them first
				if (uploader.files.length > 0) {
					// When all files are uploaded submit form
					uploader.bind('StateChanged', function() {
						if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
							$('form')[0].submit();
						}
					});

					uploader.start();
				} else {
					alert('You must queue at least one file.');
				}

				return false;
			});
		});
	</script>
</head>
<body>
	<?php echo Form::open(array('enctype'=>'multipart/form-data',)); ?>
		<div id="uploader">
			<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
		</div>
	<?php echo Form::close(); ?>
</body>
</html>