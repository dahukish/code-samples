<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Mediahandler</title>

	<?php
		$asset = Asset::forge('plupload', array('paths' => array('assets/plupload/')));

		# js paths
		$asset->add_path('assets/plupload/','js');
		$asset->add_path('assets/plupload/jquery.ui.plupload/','js');
		$asset->add_path('assets/plupload/jquery.plupload.queue','js');

		# css paths
		$asset->add_path('assets/css/','css');
		$asset->add_path('assets/plupload/jquery.ui.plupload/css/','css');
		$asset->add_path('assets/plupload/jquery.plupload.queue/css/','css');

		# img paths
		$asset->add_path('assets/plupload/jquery.ui.plupload/img/','img');
		$asset->add_path('assets/plupload/jquery.plupload.queue/img/','img');

		$asset->css(array('jquery.plupload.queue.css'),array(),'plupload',false);

		if($view === 'ajax') $asset->css(array('bootstrap.css'),array(),'plupload',false);
		if($view === 'ajax') $asset->js(array('http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js'),array(),'plupload',false);

		// Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now)
		$asset->js(array('http://bp.yahooapis.com/2.4.21/browserplus-min.js'),array(),'plupload',false);

		// Load plupload and all it's runtimes and finally the jQuery queue widget
		$asset->js(array('plupload.full.js'),array(),'plupload',false);
		$asset->js(array('jquery.plupload.queue.js'),array(),'plupload',false);

		echo $asset->render('plupload');
	?>

	<script>
		var parent_obj = null;

		// Convert divs to queue widgets when the DOM is ready
		$(function() {
			parent_obj = $(window.parent.document);
			parent_obj.find('#save_link').hide();

			$("#uploader").pluploadQueue({
				// General settings
				runtimes: 'gears,flash,silverlight,browserplus,html5',
				url: '/mediahandler/handler/plupload',
				max_file_size: '10mb',
				chunk_size: '1mb',
				unique_names: true,

				// Specify what files to browse for
				filters: [
					<?=htmlspecialchars_decode($filters)?>
				],

				// Flash settings
				flash_swf_url: '/assets/plupload/plupload.flash.swf',

				// Silverlight settings
				silverlight_xap_url: '/assets/plupload/plupload.silverlight.xap',

				// Post init events, bound after the internal events
				init: {
					Refresh: function(up) {
						resizeIFrame();
					},
					FileUploaded: function(up, file, info) {
						parent_obj.find('#save_link').show();

						var data = new Object();

						data.is_plupload = 1;
						data.name = file.name;
						data.size = file.size;
						data.filename = file.target_name;

						$.ajax({
							type: 'POST',
							data: data,
							dataType: 'json',
							url: '/mediahandler/file/create',
							success: function(saved) {
								if (!saved.status)
								{
									if (typeof saved.body === 'object')
									{
										var error_msg = "";

										for (var field in saved.body)
										{
											error_msg = field+": "+saved.body[field].rule+"\n";
										}

										alert(error_msg);
									}
									else
									{
										alert(saved.body);
									}
								}
							}
						});

						console.log('[FileUploaded] File:', file, "Info:", info);
					},
					UploadComplete: function(up, files) {

						setTimeout(function() {
							var ts=(new Date()).getTime();
							$('#browse').load('<?=$browse_path?>&ts='+ts,function(x) {
								$('div.tab-pane').hide();
								prepareBrowseTab();
								$('#browse').show({complete:function() { resizeIFrame(); }});
							});
						},1000);

					},
					Error: function(up, args) {
						console.log('[error] ', args);
					}
				}
			});

			// Client side form validation
			$('form#plupload').submit(function(e) {
				parent_obj = $(window.parent.document);
				parent_obj.find('#save_link').show();
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


			$('form#external').submit(function(e) {
				parent_obj = $(window.parent.document);
				parent_obj.find('#save_link').css('visibility','visible');
				var data = new Object();

				$(this).find(':input').each(function(index, element){
					var name = $(element).attr('name');
					var value = $(element).val();
					data[name] = value;
				});

				$.ajax({
					type: 'POST',
					data: data,
					url: '/mediahandler/file/create',
					success: function(saved) {
						if (!saved.status)
						{
							if (typeof saved.body === 'object')
							{
								var error_msg = "";

								for (var field in saved.body)
								{
									error_msg = field+": "+saved.body[field].rule+"\n";
								}

								alert(error_msg);

							}
							else
							{
								alert(saved.body);
							}
						}
						else
						{
							$('div#created-queue').append('<div data-id="'+saved.body.id+'" data-mime="'+saved.body.mime+'">'+saved.body.name+'</div>');

							// flush the values
							$('form#external').find(':input').each(function(index, element) {
								switch($(element).attr('name'))
								{
									case 'name':
									case 'ext_url':
									case 'embed_code':
									case 'alt_text':
										$(element).val('');
									break;
									case 'height':
									case 'width':
										$(element).val(0);
									break;
								}
							});
						}
					},
					dataType: 'json'
				});

				return false;
			});

			prepareBrowseTab();

			$('a[data-toggle="tab"]').on('click', function (e) {
				var id = $(this).attr('href');
				$('div.tab-pane').hide();
				$(id).show({complete:function() { resizeIFrame(); }});
			});

			$('<?=$tab?>').show();

			resizeIFrame();
		});

		function prepareBrowseTab() {

			$('form#browse button').on("click",function(e) {

				parent_obj = $(window.parent.document);
				var ids = [];
	
				<?php 
					if ($mode=="wysiwyg") {
						if ($max>1) {
				?>
							$('form#browse').find(':checked').each(function(index,item) {

								var path=$(item).data('path');
								parent.currentEditor.insertHtml('<figure data-id="'+$(item).val()+'"><img src="/'+path+'" /></figure>');

							});
				<?php
						}
						else {
				?>
							var path=$(this).data('path');
							var id=$(this).data('id');
							parent.currentEditor.insertHtml('<figure data-id="'+id+'"><img src="/'+path+'" /></figure>');

				<?php	} ?>

						parent.plupload_closeWindow();

				<?php }
					  else { 
						if ($max>1) {
				?>
							var out=parent_obj.find('div#<?=$prefix?>_wrap .changeable').html();

							$('form#browse').find(':checked').each(function(index,item) {
								ids.push($(item).val());

								var mime_arch = /^[^\/]+/i.exec($(item).data('mime'));
								out+='<div id="<?=$prefix?>_'+mime_arch[0]+'"><div class="preview-media-item" data-id="'+$(item).data("id")+'"><img src="/'+$(item).data('cached-img')+'" alt="thumb" /><div class="media-item">'+$(item).data('name')+'<a href="#" class="submit" data-submit-type="remove-media-item" data-prefix="<?=$prefix?>">&times;</a></div></div></div>';

							});

							parent_obj.find('div#<?=$prefix?>_wrap .changeable').html(out);

							var old_val = parent_obj.find('input#form_<?=$prefix?>').val();
							if(ids.length) parent_obj.find('input#form_<?=$prefix?>').val(((old_val.length > 1)? old_val+','+ids.join(',') : ids.join(',')));
				<?php
						}
						else {
				?>
							var mime_arch = /^[^\/]+/i.exec($(this).data('mime'));
							var out='<div id="<?=$prefix?>_'+mime_arch[0]+'"><div class="preview-media-item" data-id="'+$(this).data("id")+'"><img src="/'+$(this).data('cached-img')+'" alt="thumb" /><div class="media-item">'+$(this).data('name')+'<a href="#" class="submit" data-submit-type="remove-media-item" data-prefix="<?=$prefix?>">&times;</a></div></div></div>';

							parent_obj.find('div#<?=$prefix?>_wrap .changeable').html(out);
							parent_obj.find('input#form_<?=$prefix?>').val($(this).data('id'));

				<?php	} ?>

					parent.plupload_closeWindow();

				<?php } ?>

				return false;
			});
		}

		function resizeIFrame() {
			var h=$('body').innerHeight();
			var pframe=parent.document.getElementById("mediahandler_iframe");
			pframe.style.height=h+"px";
		}
	</script>
</head>
<body>
	<ul class="nav nav-tabs">
		<li><a href="#browse" data-toggle="tab" <?=$browse_refresh?>>Browse</a></li>
		<li><a href="#upload" data-toggle="tab">Upload</a></li>
		<?php if(isset($external)){ ?><li><a href="#external" data-toggle="tab">External Files</a></li><?php } ?>
	</ul><!-- /.nav-tabs -->

	<div class="tab-content">
		<div class="tab-pane active" id="browse">
			<?php if(isset($browse)) echo htmlspecialchars_decode($browse); ?>
		</div>
		<div class="tab-pane" id="upload">
			<?php echo Form::open(array('enctype'=>'multipart/form-data','id'=>'plupload')); ?>
				<div id="uploader">
					<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
				</div>
			<?php echo Form::close(); ?>
		</div>
		<div class="tab-pane" id="external">
			<?php if(isset($external)) echo htmlspecialchars_decode($external); ?>
		</div>
	</div><!-- /.tab-content -->

<?php if (0) { ?>
	<?php if($view === 'ajax'): ?>
		<h3>Uploaded Files</h3>
		<div id="created-queue"></div>
	<?php endif; ?>
<?php } ?>

</body>
</html>