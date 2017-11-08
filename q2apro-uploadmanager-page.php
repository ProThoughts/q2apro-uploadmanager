<?php

/*
	Plugin Name: q2apro Upload Manager
	Plugin Author: q2apro.com
*/

	class q2apro_uploadmanager_page
	{
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory = $directory;
			$this->urltoroot = $urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'Uploads', // title of page
					'request' => 'uploadmanager', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='uploadmanager')
			{
				return true;
			}

			return false;
		}

		function process_request($request)
		{
			if(qa_opt('q2apro_uploadmanager_enabled')!=1)
			{
				$qa_content = qa_content_prepare();
				$qa_content['error'] = '<div>'.qa_lang_html('q2apro_uploadmanager_lang/plugin_disabled').'</div>';
				return $qa_content;
			}
			// return if permission level is not sufficient
			if(qa_user_permit_error('q2apro_uploadmanager_permission'))
			{
				$qa_content = qa_content_prepare();
				$qa_content['error'] = qa_lang_html('q2apro_uploadmanager_lang/access_forbidden');
				return $qa_content;
			}
				
			/* start content */
			$qa_content = qa_content_prepare();
			$qa_content['custom'] = '';

			// page title
			$qa_content['title'] = qa_lang('q2apro_uploadmanager_lang/page_title');

			// required for qa_get_blob_url()
			require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
			
			// OPTIONS
			$displayimages = qa_opt('q2apro_uploadmanager_displayimages');
			$show_links_to_posts = qa_opt('q2apro_uploadmanager_showpostlinks');
			$default_lastdays = 5; // check uploaded files of x days


			// Single operation - DELETE was hit by admin, AJAX
			$deleteBlobId = qa_post_text('ajaxdata');
			if(!empty($deleteBlobId)) 
			{
				$ajaxreturn = '';
				qa_delete_blob($deleteBlobId);
				echo 'deleted';
				return;
			}
			// AJAX END

			
			// find ALL blobs in ALL posted content
			// e.g. "./?qa=blob&qa_blobid=1291589109129288482" -> search for qa_blobid
			$queryUploadIds = qa_db_query_sub('SELECT postid,type,parentid,content,created
											FROM `^posts`
											WHERE `content` LIKE ("%qa_blobid%")
											;'); 
											
			$allBlobIDs = array();
			$allQuestionIDs = array(); // postids and parentids
			$allPostIDs = array(); // only postids (original for A and C)
			$allPostTypes = array();
			
			while(($post = qa_db_read_one_assoc($queryUploadIds,true)) !== null)
			{
				// save all extracted blob URLs from all posts into array $allBlobIDs
				$urls = $this->q2apro_getUrls($post['content']);
				foreach($urls as $urln)
				{
					if(strpos($urln,'qa_blobid=') !== false)
					{
						// extract blobids &qa_blobid=1291589109129288482
						$arrayhelper = explode('=',$urln);
						$allBlobIDs[] = $arrayhelper[sizeof($arrayhelper)-1];
						
						// show links to post if activated in admin options
						if($show_links_to_posts)
						{
							$allPostTypes[] = strtolower($post['type']); // q,a,c
							$allPostIDs[] = $post['postid'];
							if($post['type']=='Q')
							{
								$allQuestionIDs[] = $post['postid'];
							}
							else if($post['type']=='A')
							{
								// answer
								$allQuestionIDs[] = $post['parentid'];
							}
							else
							{
								// Comment on Question or Comment on Answer
								// remember that parentid of question is always NULL
								$parentpostid = qa_db_read_one_value(
												qa_db_query_sub('SELECT parentid FROM `^posts` 
																	WHERE `postid` = # 
																	LIMIT 1', $post['parentid']), true);
								if(isset($parentpostid))
								{
									// this is an answer, take its parent (which is Q)
									$allQuestionIDs[] = $parentpostid;
								}
								else
								{
									// this is a question
									$allQuestionIDs[] = $post['parentid'];
								}
							}
						} // end show_links_to_posts
					} // end qa_blobid
				} // end foreach urls
			} // end while posts
			
			
			/* process URL parameters */ 
			
			// you can set number of days to be shown in the URL, e.g. yoursite.com/uploadmanager?days=5
			$lastdays = qa_get('days');
			if(empty($lastdays) || $lastdays<=0)
			{
				$lastdays = $default_lastdays;
			}
			
			$startdate = qa_get('startdate');
			$enddate = qa_get('enddate');
			
			// you can set a flag in the URL to show only those images that do not exist in posts/avatars
			// e.g. yoursite.com/uploadmanager?remove=find&days=15
			$removeFlag = qa_get('remove');
			$removeMode = false;
			if(!empty($removeFlag))
			{
				// if remove=value in URL then only display unused images
				$removeMode = true;
			}
			
			// you can specifiy a username in the URL if you wish to only see images of this user
			// e.g. yoursite.com/uploadmanager?days=30&user=William35
			$gotUserName = qa_get('user');
			if(is_null($gotUserName))
			{
				$userid_toQuery = '';
			}
			else
			{
				// get userid, qa_handle_to_userid only from v1.6.3
				$theUserData = qa_db_read_one_value(
									qa_db_query_sub('SELECT userid FROM ^users 
														WHERE handle = # 
														LIMIT 1', $gotUserName), 
									true);
				// $theUserData = mysql_fetch_array($useridRowQuery);
				if(!empty($theUserData)) {				
					$userid_toQuery = 'AND userid = "'.$theUserData.'" ';
				}
				else {
					$userid_toQuery = '';
					// username does not exist
					$qa_content['error'] = qa_lang('q2apro_uploadmanager_lang/user_not_existing');
					return $qa_content;
				}
			}

			
			$timed_query = 'AND created > NOW() - INTERVAL '.$lastdays.' DAY ';
			if(isset($startdate) && isset($enddate))
			{
				if($startdate==$enddate)
				{
					// add one day to enddate
					$enddate = date('Y-m-d', strtotime($startdate)+60*60*24*1);
				}
				$timed_query = 'AND created > "'.date('Y-m-d H:i:s', strtotime($startdate)).'" 
								AND created < "'.date('Y-m-d 00:00:00', strtotime($enddate)+60*60*24*1 ).'" '; // e.g. 2013-10-11 12:45:43
			}
			else
			{
				$startdate = date('Y-m-d', time()-60*60*24*7);
				$enddate = date('Y-m-d');
			}
			// query blobs of last x days or in date range, and minimum age of 60 min
			$queryRecentUploads = qa_db_query_sub('SELECT blobid, format, userid, created, createip, filename
											FROM `^blobs`
											WHERE created < NOW() - INTERVAL 60 MINUTE '.
											$timed_query.' '.
											$userid_toQuery.' '.
											'ORDER BY created DESC
											;'); 
											// LIMIT 0,100
											// ORDER BY created DESC - dont use it for better performance
											
			// counter for custom html output
			$imgCount = 1;
			$imgDelCount = 1;
			$imageformats = array('png','gif','jpeg','jpg');
			
			// initiate output string
			$listAllUploads = '';
			
			$listAllUploads .= '
			<table> 
				<thead><tr>
					<th class="column1">'.qa_lang('q2apro_uploadmanager_lang/th_number').'</th>
					<th class="column1">'.qa_lang('q2apro_uploadmanager_lang/upload_date').'</th>  
					<th>'.qa_lang('q2apro_uploadmanager_lang/media_item').'</th> 
					<th>'.qa_lang('q2apro_uploadmanager_lang/media_size').'</th> 
					<th class="column2">'.qa_lang('q2apro_uploadmanager_lang/upload_by_user').'</th> 
				</tr></thead>
			';
			
			while(($blobrow = qa_db_read_one_assoc($queryRecentUploads,true)) !== null)
			{
				$blobindb = true;
				
				// get size of image
				$blobsize = qa_db_read_one_value(
								qa_db_query_sub('SELECT OCTET_LENGTH(content) FROM `^blobs` 
													WHERE blobid=# LIMIT 1', $blobrow['blobid']),
								true);

				if(empty($blobsize))
				{
					$blobsize = filesize( qa_get_blob_filename($blobrow['blobid'], $blobrow['format']) );
					$blobindb = false;
				}
				$filesizeoutput = round($blobsize/1000, 1).' kB';
				if(!$blobindb)
				{
					$filesizeoutput .= '<br /> <span style="font-size:11px;color:#00F;">'.qa_lang('q2apro_uploadmanager_lang/file_onserver').'</span>';
				}
				else
				{
					$filesizeoutput .= '<br /> <span style="font-size:11px;color:#0A0;">'.qa_lang('q2apro_uploadmanager_lang/file_indatabase').'</span>';
				}
				
				// check if blobid exists in posts (containing used blobids)
				// if(in_array($blobrow['blobid'], $allBlobIDs)) {
				$existsInPost = false;
				$link_entry = '';
				
				// find blobid in all existing content blobids
				$id_arraypos = array_search($blobrow['blobid'], $allBlobIDs);
				if($id_arraypos)
				{
					$existsInPost = true;
					if($show_links_to_posts)
					{
						$posttyper = $allPostTypes[$id_arraypos];
						$questionid = $allQuestionIDs[$id_arraypos];
						$linkpostid = $allPostIDs[$id_arraypos];
						$urlanchor = ($posttyper=='q') ? '' : '?show='.$linkpostid.'#'.$posttyper.$linkpostid;
						$post_type = 'question';
						if($posttyper=='a') {
							$post_type = 'answer';
						}
						else if($posttyper=='c') {
							$post_type = 'comment';
						}
						// create link with posttype and anchor
						$link_entry = '<br />
									   <a class="filepostlink" target="_blank" href="'.qa_opt('site_url').$questionid.$urlanchor.'">'.
										qa_lang('q2apro_uploadmanager_lang/file_usedin').' '.$post_type.' '.$linkpostid.
									  '</a>';
					}
					else
					{
						$link_entry = '';						
					}
				}
				else
				{
					$existsInPost = false;
					$link_entry = '<br />
						<span class="tred">'.qa_lang('q2apro_uploadmanager_lang/not_found').' &rarr; <a class="delImageLink" data-original="'.$blobrow['blobid'].'">'.qa_lang('q2apro_uploadmanager_lang/delete_file').'</a>
						</span>';
				}
				
				$isavatar = false;
				// check if image is used as user avatar
				if(!$existsInPost)
				{
					$userid_avatar = qa_db_read_one_value( 
											qa_db_query_sub('SELECT userid FROM `^users` 
															WHERE `avatarblobid` LIKE # 
															LIMIT 1', $blobrow['blobid']), true);
					if($userid_avatar)
					{
						$existsInPost = true;
						$isavatar = true;
						$link_entry = '<span style="color:#090">&rarr; '.qa_lang('q2apro_uploadmanager_lang/is_avatar').'</span>';
					}
					else
					{
						// check if image is used as default avatar (within table qa_options, field avatar_default_blobid)
						$userid_avatar_def = qa_db_read_one_value( 
												qa_db_query_sub('SELECT title FROM `^options` 
																WHERE `content` LIKE # 
																LIMIT 1', $blobrow['blobid']), true);
						if($userid_avatar_def)
						{
							$existsInPost = true;
							$isavatar = true;
							$link_entry = '<span style="color:#090">&rarr; used as default avatar image</span>';
						}
					}
				}
				
				// check if image is used in custom pages
				$existsInPage = qa_db_read_one_value(
									qa_db_query_sub('SELECT tags FROM `^pages` 
														WHERE `content` LIKE "%'.$blobrow['blobid'].'%" 
														LIMIT 1
														'), true);
				if(!empty($existsInPage))
				{
					$existsInPost = true;
					$link_entry = '<span style="color:#09C;">&rarr; used in custom page: "'.$existsInPage.'"</span>';
				}
				
				$itemdisplay = '';
				if($isavatar)
				{
					$itemdisplay = '<img class="listSmallImages" src="'.qa_path_html('image', array('qa_blobid' => $blobrow['blobid'], 'qa_size' => 250), null, QA_URL_FORMAT_PARAMS).'">';
				}
				// blob is an image
				else if(in_array($blobrow['format'], $imageformats))
				{
					if($displayimages)
					{
						$itemdisplay = '<span>'.$blobrow['filename'].'</span> <br/> 
										<img class="listSmallImages" src="'.qa_get_blob_url($blobrow['blobid']).'">';
					}
					else
					{
						$itemdisplay = '<a class="tooltipW" title="<img src=\''.qa_get_blob_url($blobrow['blobid']).'\'>" href="'.qa_get_blob_url($blobrow['blobid']).'">'.$blobrow['filename'].'</a>';
					}
				}
				// blob is a document
				else
				{
					$itemdisplay = '<a href="'.qa_get_blob_url($blobrow['blobid']).'">'.$blobrow['filename'].'</a>';
				}
				
				// more performant
				require_once QA_INCLUDE_DIR.'qa-app-posts.php';
				$username = qa_post_userid_to_handle($blobrow['userid']);
				$linkUsername = isset($blobrow['userid']) 
									? '<a class="qa-user-link" href="'.qa_path('user').'/'.$username.'">'.$username.'</a>' 
									: qa_ip_anchor_html( long2ip($blobrow['createip']), qa_lang('main/anonymous'));
				$rowString = '
				<tr>
					<td>'.($removeMode ? $imgDelCount : $imgCount).'</td>
					<td>'.substr($blobrow['created'],0,16).'</td> 
					<td>'.$itemdisplay.'<br />
						<span class="tgray">id: '.$blobrow['blobid'].'</span> '.$link_entry.'<br />'
						.($blobrow['format']=='jpeg' || $blobrow['format']=='jpg' ? '<a class="tgreen" href="rotateimage?id='.$blobrow['blobid'].'" target="_blank">rotate?</a>' : '').
					'</td> 
					<td>'.$filesizeoutput.'</td> 
					<td>'.$linkUsername.'</td> 
				</tr>
				';
			
				// count to-be-deleted item
				if(!$existsInPost)
				{
					$imgDelCount++;
				}
				
				// list only images to be deleted or all images
				if($removeMode)
				{
					if(!$existsInPost)
					{
						$listAllUploads .= $rowString;
					}
				}
				else
				{
					// show all images
					$listAllUploads .= $rowString;
				}
				// image count
				$imgCount++;
			}
			$listAllUploads .= '</table>';

			
			/* output into theme */
			
			// inputs for date range
			$qa_content['custom'] .= '
			<form class="ludatr">
				<label for="startdate">Start: </label>
				<input type="text" name="startdate" value="'.$startdate.'" />
				<label for="enddate">End: </label>
				<input type="text" name="enddate" value="'.$enddate.'" />
				<input class="qa-form-tall-button" type="submit" value="'.qa_lang('q2apro_uploadmanager_lang/show_button').'" />
			</form>
			';
			
			// remove link
			$showremove_link = qa_path('uploadmanager').'?remove=true';
			$params = $_GET;
			if(!empty($params)) {
				unset($params['remove']);
				$params['remove'] = 'true';
				$showremove_link = qa_path('uploadmanager').'?'.http_build_query($params);
			}
			
			// ?remove=true
			// $showremove_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			
			$qa_content['custom'] .= '<p style="margin-top:10px;">';
			if($removeMode)
			{
				$qa_content['custom'] .= '<a class="btnblue" href="'.qa_path('uploadmanager').'">'.qa_lang('q2apro_uploadmanager_lang/show_all').'</a>';
			}
			else
			{
				$qa_content['custom'] .= '<a class="btnblue" href="'.$showremove_link.'">'.qa_lang('q2apro_uploadmanager_lang/show_unused').'</a>';
			}			
			$rotatelink = qa_path('rotateimage', null, qa_opt('site_url'));
			$qa_content['custom'] .= '</p>';
			$qa_content['custom'] .= '
			<p>
				<a class="imgrotatepagelink" target="_blank" href="'.$rotatelink.'">'.qa_lang('q2apro_uploadmanager_lang/rotate_page').'</a>
			</p>
			';

						
			// statistics
			$blobcount = qa_db_read_one_value(qa_db_query_sub('SELECT COUNT(*) FROM `^blobs`'));
			// http://stackoverflow.com/questions/9620198/how-to-get-the-sizes-of-the-tables-of-a-mysql-database
			// $blobssize = 0;

			$qa_content['custom'] .= '<p>You have '.$blobcount.' files in your blob-table.</p>';
			
			$qa_content['custom'] .= '<p>'.qa_lang('q2apro_uploadmanager_lang/number_files_remove').': '.($imgDelCount-1).'</p>';
			
			if($imgDelCount>1)
			{
				$qa_content['custom'] .= '<p><a id="removethemall">'.qa_lang('q2apro_uploadmanager_lang/delete_unusedfiles').'</a></p>';
				$qa_content['custom'] .= '<p id="delcountr" style="display:none;">...</p>';
			}
	
			$qa_content['custom'] .= '<div class="listuploads">'.$listAllUploads.'</div>';
			
			
			// show admin tip how to use parameters in URL
			$qa_content['custom'] .= '
								<div id="upman_info">
									<p><b>Instructions for Admin:</b></p>
									<p>Use URL parameters to filter images: /uploadmanager?<span style="color:#F00">days=30</span>&amp;<span style="color:#090">remove=find</span>&amp;<span style="color:#00F">user=William35</span></p>
									<p><span style="color:#F00">days=30</span> &rarr; sets number of days to be shown</p>
									<p><span style="color:#090">remove=find</span> &rarr; show only images that do not exist in posts/avatars/pages</p>
									<p><span style="color:#00F">user=William35</span> &rarr; show only images of certain user</p>
								</div>';
			
			// tipsy script
			$qa_content['custom'] .= '
				<script type="text/javascript" src="'.$this->urltoroot.'tipsy.script.mod.js"></script>
			';
			
			$qa_content['custom'] .= '
			<script type="text/javascript">
				$(document).ready(function()
				{
				
					var ajaxblobid;
					var clicked; 
					
					$(".delImageLink").click( function(e) {
						e.preventDefault();
						clicked = $(this);
						doAjaxPost();
					});
					
					function doAjaxPost() {
						// get postid from input
						ajaxblobid = clicked.attr("data-original"); 
						
						// send ajax request
						$.ajax({
							 type: "POST",
							 url: "'.qa_self_html().'",
							 data: { ajaxdata: ajaxblobid },
							 cache: false,
							 success: function(data) {
								// dev
								console.log("server returned: "+data);
								// output result in DIV
								clicked.parent().parent().parent().fadeOut();
							 },
							 error: function(data) {
								alert("Ajax error");
							 }
						});
					}
					
					$("#removethemall").click( function(e) {
						e.preventDefault();
						$("#delcountr").show();
						
						var filecount = $(".delImageLink").length;
						var delcount = $(".delImageLink").length;
						
						$(".delImageLink").each( function(index) {
							var recentlink = $(this);
							recentlink.parent().parent().parent().fadeOut(500, function() { 
								// *** implement warnonleave to prevent accidental closing
								setTimeout(function() {
									recentlink.trigger("click")
									delcount--;
									$("#delcountr").text("deleting file number: "+(filecount-delcount)+" / "+filecount);
									if(delcount==0) {
										$("#delcountr").html("<span style=\'color:#00F;\'>job finished</span>");									
									}
								}, 500*index);
								
							});
						});
					});
					
				}); // end ready
			</script>
			';
			
			// custom CSS
			$qa_content['custom'] .= '<style type="text/css">
				.qa-main {
					position:relative;
					font-family:sans-serif;
					font-size:13px;
				}
				.ludatr {
					margin:30px 0;
				}
				.ludatr input[type="text"] {
					border:1px solid #CCC;
					padding:5px;
					width:70px;
					font-size:13px;
				}
				.listuploads {
					border-radius:0;
					padding:0;
					margin-top:-2px;
					font-size:13px;
				}
				.listuploads table thead tr th {
					background-color:#cfc;
					border:1px solid #CCC;
					padding:4px;
				} 
				.listuploads table {
					background-color:#F5F5F5;
					margin:30px 0 15px;
					text-align:left;
					border-collapse:collapse;
				} 
				.listuploads td { 
					border:1px solid #CCC;
					padding:1px 10px;
					line-height:25px;
					vertical-align:top;
				}
				.listuploads tr:hover{
					background:#FFC;
				} 
				.listuploads .column1, .listuploads .column2 {
					text-align:center;
				}
				.listuploads td img {
					border:1px solid #DDD !important;
					margin-right:5px;
				}
				.listuploads .listSmallImages { 
					max-width:350px;
					max-height:150px; 
					margin: 5px 0; 
					cursor:pointer;
				}
				.listuploads .delImageLink {
					/*text-decoration:underline !important;*/
					color:#F00;
					cursor:pointer;
				} 
				.listuploads td .tgray {
					color:#777;
					font-size:11px;	
				}
				.listuploads td .tgreen {
					color:#0A0;
					font-size:11px;
					float:right;
					text-decoration:underline;
				}
				.listuploads .tred {
					color:#F00;
				}
				#upman_info {
					padding:20px;
					border:1px solid #CCC;
					border-radius:3px;
					background:#FFFFF5;
				}
				#removethemall {
					text-decoration:underline;
					color:#00F;
					cursor:pointer;
				}
				.imgrotatepagelink {
					position:absolute;
					top:0;
					right:0;
				}
				.filepostlink {
					color:#123;
					display:inline-block;
					margin-bottom:30px !important;
					background: #FFE;
					padding: 1px 3px;
				}
				.btnblue {
				    position: relative;
					overflow: visible;
					display: inline-block;
					padding: 5px 12px;
					text-decoration: none !important;
					border: 1px solid #3072b3;
					border-bottom-color: #2a65a0;
					margin: 4px 0 2px;
					text-shadow: -1px -1px 0 rgba(0,0,0,.3);
					font-size: 12px;
					color: #FFF !important;
					white-space: nowrap;
					cursor: pointer;
					outline: 0;
					background-color: #3C8DDE;
					background-image: linear-gradient(#599bdc,#3072b3);
					border-radius: 0.2em;
					margin-right:15px;
				}
				
				#lightbox-popup{ background:#000000; background:rgba(0,0,0,0.75); height:100%; width: 100%; position:fixed; top:0; left:0; display: none; z-index:1119; } #lightbox-center{ margin:6% auto; width:auto; text-align:center; } img#lightbox-img { padding:25px; background:#FFF }

			</style>';
			
			// jquery lightbox effect: if you click an image, it opens in a popup 
			$qa_content['custom'] .= '<div id="lightbox-popup"> <div id="lightbox-center">  <img id="lightbox-img" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D" alt="Lightbox" />  </div> </div>';
			
			$qa_content['custom'] .= '
			<script type="text/javascript">
				$(document).ready(function()
				{ 
					// lightbox effect for images
					$(".listSmallImages").click(function(e){
						e.preventDefault();
						$("#lightbox-popup").fadeIn("fast");
						$("#lightbox-img").attr("src", $(this).attr("src"));
						// center vertical
						$("#lightbox-center").css("margin-top", ($(window).height() - $("#lightbox-center").height())/2  + "px");
					});
					$("#lightbox-popup").click(function(){
						$("#lightbox-popup").fadeOut("fast");
					});
					
				});
			</script>
			';
			
			return $qa_content;
		}
		
		function q2apro_getUrls($string)
		{
			//$regex = '/https?\:\/\/[^\" ]+/i';
			$regex = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
			preg_match_all($regex, $string, $matches);
			return $matches[0];
		}

	}; // end class q2apro_uploadmanager_page
	

/*
	Omit PHP closing tag to help avoid accidental output
*/