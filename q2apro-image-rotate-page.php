<?php

/*
	Plugin Name: q2apro Upload Manager
	Plugin Author: q2apro.com
*/

	class q2apro_image_rotate_page 
	{
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'Image Rotate', // title of page
					'request' => 'rotateimage', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='rotateimage')
			{
				return true;
			}

			return false;
		}

		function process_request($request)
		{
		
			$userid = qa_get_logged_in_userid();
			$level = qa_get_logged_in_level();

			if($level < QA_USER_LEVEL_EDITOR) 
			{
				$qa_content = qa_content_prepare();
				// page title
				$qa_content['title'] = qa_lang('q2apro_uploadmanager_lang/page_title_rotate'); 
				$qa_content['error'] = '<p>'.qa_lang('q2apro_uploadmanager_lang/not_allowed').'</p>';
				return $qa_content;
			}

			// AJAX post: we received post data, so it should be the ajax call
			$blobin = qa_post_text('ajaxdata');
			
			if(!empty($blobin)) 
			{
				
				$ajaxreturn = '';
				
				require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
				require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
				
				// default: blobin is only blobid
				$blobid = $blobin;
				// in case it is an image link, e.g. ?qa=blob&qa_blobid=8504821657387716690
				// strip the blobid from the url
				if(strpos($blobin,'qa_blobid=')!== false)
				{
					// extract the blobid from the blobURL
					$urlArray = explode('=',$blobin);
					$blobid = $urlArray[sizeof($urlArray)-1];
				}
				
				// read image blob
				$blobdata = qa_read_blob($blobid);

				if(isset($blobdata))
				{
					$savesuccess = false;
					// rotate JPG
					if($blobdata['format']=='jpg' || $blobdata['format']=='jpeg')
					{
						$degrees = -90;
						$source_image = imagecreatefromstring($blobdata['content']);
						$rotate_image = imagerotate($source_image, $degrees, 0); // last is background color
						ob_start();
						imagejpeg($rotate_image, null, 90);
						$image_bin = ob_get_contents();
						ob_end_clean(); 
						
						$blobmeta = qa_db_blob_read($blobid);
						if(empty($blobmeta['content']))
						{
							// FILE SYSTEM
							$savesuccess = $this->qa_update_blob_file($blobid, $image_bin, $blobdata['format']);
						}
						else
						{
							// DATABASE
							$savesuccess = qa_db_query_sub('UPDATE ^blobs SET content=$ WHERE blobid=#', $image_bin, $blobid);
						}
						
						// free the memory
						imagedestroy($source_image);
						imagedestroy($rotate_image);
					}
					else
					{
						// filetype not supported
						$ajaxreturn .= '<p style="margin:10px 0 20px 0;font-size:15px;">'.qa_lang('q2apro_uploadmanager_lang/error_filetype').'</p>';
						echo $ajaxreturn;
						return;
					}

					// rotate PNG, for later
					/*
					else if($blob['format'] == 'png') {
						$filename = 'YourFile.png';
						$rotang = 20; // Rotation angle
						$source = imagecreatefrompng($filename) or die('Error opening file '.$filename);
						imagealphablending($source, false);
						imagesavealpha($source, true);

						$rotation = imagerotate($source, $rotang, imageColorAllocateAlpha($source, 0, 0, 0, 127));
						imagealphablending($rotation, false);
						imagesavealpha($rotation, true);

						header('Content-type: image/png');
						imagepng($rotation);
						imagedestroy($source);
						imagedestroy($rotation);
					}*/
				
					// output success message
					if($savesuccess)
					{
						$ajaxreturn .= '<p style="margin:10px 0 20px 0;font-size:15px;">'.qa_lang('q2apro_uploadmanager_lang/rotate_success').'</p>';
						// for image refreshing we need to add a time id
						$ajaxreturn .= '<img src="?qa=blob&qa_blobid='.$blobid.'&time='.time().'" />';
					}
					else
					{
						$ajaxreturn .= '<p style="margin:40px 0 20px 0;font-size:15px;">'.qa_lang('q2apro_uploadmanager_lang/error3').'</p>';
					}
				}
				else
				{
					$ajaxreturn = qa_lang('q2apro_uploadmanager_lang/blob_not_exist');
				}
				echo $ajaxreturn;
				return;
				// end is blobid
			} // end AJAX return

			
			// check if we have an image id in the URL
			$imgid = qa_get('id');

			// start content
			$qa_content = qa_content_prepare();

			// page title
			$qa_content['title'] = qa_lang('q2apro_uploadmanager_lang/page_title_rotate'); 

			// init
			$qa_content['custom'] = '';
			
			// some CSS styling
			$qa_content['custom'] .= '
			<style type="text/css">
				#indiv { border-left:10px solid #ABF;margin:20px 0 0 5px;padding:5px 10px; }
				.rotatehint { font-size:13px;}
				.qa-main h1 { margin-bottom:40px; }
				input#blobid_input { border:1px solid #EEE; padding:3px; margin-bottom:15px; min-width:200px; }
				.linkresult { display:table; }
				.linkresult p { display: table-row; }
				.linkresult span { display: table-cell; padding-right:10px; line-height:200%; min-width:70px;}
				#ajaxresult {
					padding:10px;
					background:#F5F5F5;
					margin:25px;
					border:1px solid #EEE;
				}
				#postcontent_head {
					border-top:1px solid #555;
					padding-top:15px;
					margin:15px 0 10px 0;
					font-weight:bold;
				}
			</style>';
			
			
			// if $imgid is set, trigger rotate
			$triggerbabe = '';
			$inputval = '';
			if(isset($imgid)) 
			{
				$triggerbabe = 'doAjaxPost();';
				$inputval = $imgid;
			}
			
			// default page with input dialog
			$qa_content['custom'] .= '<p class="rotatehint">'.qa_lang('q2apro_uploadmanager_lang/rotate_hint').'</p>';
			$qa_content['custom'] .= '<div id="indiv">
											<input value="'.$inputval.'" name="blobid_input" id="blobid_input" type="text" placeholder="'.qa_lang('q2apro_uploadmanager_lang/input_placeholder').'" autofocus>
											<br />
											<span class="btnblue" id="submitbtn">'.qa_lang('q2apro_uploadmanager_lang/submitbutton').'</span>
										 </div>';
			$qa_content['custom'] .= '<div id="ajaxresult"></div>';
			
			$qa_content['custom'] .= '
			<script type="text/javascript">
				$(document).ready(function(){
					$("#submitbtn").click( function() { 
						doAjaxPost();
					}); // end click
					$("#blobid_input").keyup(function(e) {
						// if enter key
						if(e.which == 13) { 
							doAjaxPost();
						}
					});

					function doAjaxPost() {
						// get blobid from input
						var imageblobid = $("#blobid_input").val(); 
						// send ajax request
						$.ajax({
							 type: "POST",
							 url: "'.qa_self_html().'",
							 data: { ajaxdata: imageblobid },
							 cache: false,
							 success: function(data) {
								//dev
								console.log("server returned:"+data);
								// output result in DIV
								$("#ajaxresult").html( data );
							 },
							 error: function(data) {
								console.log("Ajax error");
							 }
						});
					}
					'
					.$triggerbabe.
					'
				});
			</script>
			
			';
			
			return $qa_content;
		} // end process_request
		
		
		function qa_update_blob_file($blobid, $content, $format)
		/*
			Update the on-disk file for blob $blobid with $content and $format. Returns true if the write succeeded, false otherwise.
		*/
		{
			$written = false;

			$directory = qa_get_blob_directory($blobid);
			if (is_dir($directory))
			{
				$filename = qa_get_blob_filename($blobid, $format);

				$file = fopen($filename, 'wa+'); // xb would create a new file, however, we replace it
				if(is_resource($file)) 
				{
					if(fwrite($file, $content)>=strlen($content))
						$written = true;

					fclose($file);

					if(!$written)
						unlink($filename);
				}
			}

			return $written;
		}

	}; // end class
	

/*
	Omit PHP closing tag to help avoid accidental output
*/