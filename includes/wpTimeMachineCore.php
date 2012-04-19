<?php

if ($_GET['clear_log'] == "true") {
	if (file_exists( wpTimeMachineLog )) {
		$log_message = "[".date("Y-m-d g:i:s a")."] *** log cleared ***";
		$log_handle = fopen(wpTimeMachineLog, 'w');
		fwrite($log_handle, $log_message);
		fclose($log_handle);
	}
	exit;
}

function wpTimeMachine_init($offsites)
{

	// Record start time

	$wpTimeMachine_start = time();

	// Get / Set Plugin options

	$wpTimeMachineOptionsStorage = wpTimeMachine_getAdminOptions();

	if (isset($_GET['show_info'])) {
		$wpTimeMachineOptionsStorage['show_info'] = $_GET['show_info'];
	}

	if (isset($_GET['use_log'])) {
		$wpTimeMachineOptionsStorage['use_log'] = $_GET['use_log'];
	}

	if (isset($_GET['show_options'])) {
		$wpTimeMachineOptionsStorage['show_options'] = $_GET['show_options'];
	}

	if (isset($_GET['format'])) {
		$wpTimeMachineOptionsStorage['format'] = $_GET['format'];
	}

	if (isset($_GET['use_post_pub'])) {
		$wpTimeMachineOptionsStorage['use_post_pub'] = $_GET['use_post_pub'];
	}
	
	if (isset($_GET['use_timestamp_dir'])) {
		$wpTimeMachineOptionsStorage['use_timestamp_dir'] = $_GET['use_timestamp_dir'];
	}

	if (isset($_GET['exclude_cache'])) {
		$wpTimeMachineOptionsStorage['exclude_cache'] = $_GET['exclude_cache'];
	}

	if (isset($_POST['offsite'])) {
		$wpTimeMachineOptionsStorage['offsite'] = $_POST['offsite'];
	}

	if (isset($_POST['remote_user'])) {
		$wpTimeMachineOptionsStorage['remote_user'] = $_POST['remote_user'];
	}

	if (isset($_POST['remote_host'])) {
		$wpTimeMachineOptionsStorage['remote_host'] = $_POST['remote_host'];
	}

	if (isset($_POST['remote_path'])) {
		$wpTimeMachineOptionsStorage['remote_path'] = $_POST['remote_path'];
	}

	if (isset($_GET['remote_pass_storage'])) {
		$wpTimeMachineOptionsStorage['remote_pass_storage'] = $_GET['remote_pass_storage'];

		if ($_GET['remote_pass_storage'] == "false") {
			$wpTimeMachineOptionsStorage['remote_pass'] = "";
		}
	}

	if (isset($_POST['remote_pass'])) {
		if ($wpTimeMachineOptionsStorage['remote_pass_storage'] == "true") {
			$wpTimeMachineOptionsStorage['remote_pass'] = $_POST['remote_pass'];
		}
	}
	
	if ( $_GET['set_offsite'] == 1 ) {
	
        $wpTimeMachineOptionsStorage['offsite'] = $_GET['offsite'];
        
        echo "set offsite to: " . $_GET['offsite'];
	
	}
	
	update_option(adminOptionsName, $wpTimeMachineOptionsStorage);
	
	if ( $_GET['ajax_settings_only'] == 1 ) {
	
        exit;
	
	}

	// Variables, based on options

	// current offsite provider
	$offsite = $wpTimeMachineOptionsStorage['offsite'];

	// 2-dimensional array with all offsite provider's metadata
	$offsites = unserialize(wpTimeMachineOffsites);

	// array with translatable text ...
	$wpTimeMachineText = unserialize(wpTimeMachineText);

	// PHP4 is currently forced to use FTP exclusively
	if (wpTimeMachinePHP == 'php4') {
		$offsite = 'ftp';
	}

	$use_log = $wpTimeMachineOptionsStorage['use_log'];

	if ($wpTimeMachineOptionsStorage['format'] == "zip") {
		$format = ".zip";
	} else {
		$format = ".tar.gz";
	}

	$files = array( wpcontent_archive.$format, wpdata_sql, htaccess_archive, restoration, instructions );

    wpTimeMachine_clean( $files );
    
    if ($_GET['clean'] == 1) {
    
        exit;
    
    }
        		
	if ($wpTimeMachineOptionsStorage['use_timestamp_dir'] == "true") {
		$timestamp = date("Y-m-d");
	} else {
		$timestamp = "";
	}

	// Start log

	wpTimeMachine_logger( $use_log,  'newlines' );

	if ($_GET['ajax'] == 1) {

		// For Ajax requests' logging
		wpTimeMachine_logger( $use_log,  '*** wpTimeMachine ajax request ***' );
		foreach ($_REQUEST as $key => $value) {
			wpTimeMachine_logger( $use_log,  '--- $_REQUEST["' . $key . '"] = ' . $value );
		}

	} else {

		wpTimeMachine_logger( $use_log,  '*** wpTimeMachine plugin loaded '.wpTimeMachineVersion.' ***' );
		wpTimeMachine_logger( $use_log,  '--- System / Environmental Checks:' );
		// Wordpress
		wpTimeMachine_logger( $use_log,  "---     Wordpress: ". wp_version );
		
		// WP_ALLOW_MULTISITE info if applicable
		if (WP_ALLOW_MULTISITE) {		
			wpTimeMachine_logger( $use_log,  "---     WP_ALLOW_MULTISITE: true" );
			wpTimeMachine_logger( $use_log,  "---     DOMAIN_CURRENT_SITE: ".DOMAIN_CURRENT_SITE );
			wpTimeMachine_logger( $use_log,  "---     PATH_CURRENT_SITE: ".PATH_CURRENT_SITE );
			wpTimeMachine_logger( $use_log,  "---     SITE_ID_CURRENT_SITE: ".SITE_ID_CURRENT_SITE );
			wpTimeMachine_logger( $use_log,  "---     DOMAIN_CURRENT_SITE: ".BLOG_ID_CURRENT_SITE );
		}

		// Browser / user-agent
		wpTimeMachine_logger( $use_log,  "---     Browser: ". $_SERVER['HTTP_USER_AGENT'] );
		// PHP Version
		wpTimeMachine_logger( $use_log,  "---     PHP: version ". phpversion() );
		// cURL extension
		if ( function_exists("curl_version") ) {
			$cURL_version = curl_version();
			wpTimeMachine_logger( $use_log,  "---     cURL: version " . $cURL_version['version']);
		} else {
			wpTimeMachine_logger( $use_log,  "---     cURL: no cURL extension");
		}
		// writable wp-content
		if ( is_writeable(wpcontent_dir) ) {
			wpTimeMachine_logger( $use_log,  "---     wp-content writable: yes");
		} else {
			wpTimeMachine_logger( $use_log,  "---     wp-content writable: no");
		}
        wpTimeMachine_logger( $use_log,  '---     Current Offsite Provider: '.$offsite );
	
	}

	// Strings based on $offsite

	$remote_user_label = $offsites[$offsite]['remote_user_label'];
	$remote_pass_label = $offsites[$offsite]['remote_pass_label'];
	$remote_path_label = $offsites[$offsite]['remote_path_label'];
	$offsite_name      = $offsites[$offsite]['offsite_name'];

	// begin plugin UI ...

?>

    <style type="text/css">

        <?php

    	if ($wpTimeMachineOptionsStorage['show_info'] == "true") {
    		echo "#Info {display:block;} ";
    	} else {
    		echo "#Info {display:none; } ";
    	}
    
    	if ($wpTimeMachineOptionsStorage['show_options'] == "true") {
    		echo ".wpTimeMachineOptions {display:block;} ";
    	} else {
    		echo ".wpTimeMachineOptions {display:none; } ";
    	}
    
        ?>

        div.wpTimeMachine_progress { background:#f7e4e5 url('<?php echo wpcontent_url; ?>/plugins/wp-time-machine/images/loading.gif') 20px 50px no-repeat; }

    </style>

    <script type="text/javascript">

        <?php

        	foreach ($offsites as $provider) {
        		echo "\n\tvar ".$provider['offsite_short']."_labels = new Array(";
        		foreach ($provider as $property) {
        			echo "\n\t\t\"".$property."\",";
        		}
        		echo "\n\t\t\"\"\n\t);";
        	}
        
            if (wpTimeMachinePHP == 'php4') {
            
                $js_offsite = "ftp";
                
            } else {
            
                $js_offsite = $wpTimeMachineOptionsStorage['offsite'];
            
            }
        
        ?>

        var offsite           = "<?php echo $js_offsite; ?>";
        var wpTimeMachinePHP  = "<?php echo wpTimeMachinePHP; ?>";
        var __nonce__         = "<?php echo wp_create_nonce('wpTimeMachine_ajax_nonce'); ?>";
        var remote_pass_label = <?php echo $wpTimeMachineOptionsStorage['offsite']; ?>_labels[1];

        function wpTimeMachine_switch_offsite_labels( offsite ) {

            wpTimeMachine_toggle_host_field( offsite );

            switch ( offsite ) {

            <?php
            
            foreach ($offsites as $provider) {
            echo "
                case \"".$provider['offsite_short']."\":
                    jQuery(\"label[for='remote_user']\").text( ".$provider['offsite_short']."_labels[0]+\": \");
                    jQuery(\"label[for='remote_pass']\").text( ".$provider['offsite_short']."_labels[1]+\": \");
                    jQuery(\"label[for='remote_path']\").text( ".$provider['offsite_short']."_labels[2]+\": \");
                    remote_pass_label = ".$provider['offsite_short']."_labels[1];
                    jQuery(\".remote_pass_label\").text(remote_pass_label);
                    jQuery(\".offsite_name\").text(".$provider['offsite_short']."_labels[3]);
                    break;";
            }
            
            ?>
            
                default:
                    // need to add error logging for this... or maybe just re-use FTP or Dropbox

            }

        }
        
        var show_info_labels = new Array(
            "<?php echo $wpTimeMachineText['show_info_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['show_info_labels'][1]; ?>"
        );
    
        var use_log_labels = new Array(
            "<?php echo $wpTimeMachineText['use_log_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['use_log_labels'][1]; ?>"
        );
    
        var show_options_labels = new Array(
            "<?php echo $wpTimeMachineText['show_options_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['show_options_labels'][1]; ?>"
        );
    
        var format_labels = new Array(
            "<?php echo $wpTimeMachineText['format_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['format_labels'][1]; ?>"
        );
    
        var use_post_pub_labels = new Array(
            "<?php echo $wpTimeMachineText['use_post_pub_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['use_post_pub_labels'][1]; ?>"
        );
        
        var use_timestamp_dir_labels = new Array(
            "<?php echo $wpTimeMachineText['use_timestamp_dir_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['use_timestamp_dir_labels'][1]; ?>"
        );
    
        var exclude_cache_labels = new Array(
            "<?php echo $wpTimeMachineText['exclude_cache_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['exclude_cache_labels'][1]; ?>"
        );
    
        var remote_pass_storage_labels = new Array(
            "<?php echo $wpTimeMachineText['remote_pass_storage_labels'][0]; ?>", 
            "<?php echo $wpTimeMachineText['remote_pass_storage_labels'][1]; ?>"
        );
    
    </script>
    
    <script type="text/javascript" src="<?php echo wpcontent_url; ?>/plugins/wp-time-machine/javascript/wp-time-machine.js"></script>

    <div class="wrap" id="wpTimeMachine">

        <?php
    
    	// start inline instructions
    
    	echo "<h2>wp Time Machine, version: " . wpTimeMachineVersion;

        	echo "<div id=\"help_links\">";
        
        	if ($wpTimeMachineOptionsStorage['show_info'] == "true") {
        		echo '<a href="javascript:void(0)" id="show_info" value="true">'.$wpTimeMachineText['show_info_labels'][0].'</a> &nbsp;&nbsp;';
        	} else {
        		echo '<a href="javascript:void(0)" id="show_info" value="false">'.$wpTimeMachineText['show_info_labels'][1].'</a> &nbsp;&nbsp;';
        	}
        
        	if ($wpTimeMachineOptionsStorage['show_options'] == "true") {
        		echo '<a href="javascript:void(0)" id="show_options" value="true">'.$wpTimeMachineText['show_options_labels'][0].'</a> &nbsp;&nbsp;';
        	} else {
        		echo '<a href="javascript:void(0)" id="show_options" value="false">'.$wpTimeMachineText['show_options_labels'][1].'</a> &nbsp;&nbsp;';
        	}
        
        	echo '<a href="http://wpTimeMachine.com" target="_new" title="Official website for the wp Time Machine">wpTimeMachine.com</a>';
        		
        	echo "</div>";

        echo "</h2>";
    
    	if ($wpTimeMachineOptionsStorage['recent_archive_name'] != "") {
    
    		echo "<div id=\"RecentInfo\">";
    		echo "<h3>Recent Archive Information:</h3>";
    		echo "<span id='recent_archive_info'>Your archives are ready, they took about " . $wpTimeMachineOptionsStorage['recent_archive_duration'] . " seconds to create;";
    		echo " and are in your ".$offsite_name." account (in this ".strtolower($remote_path_label).": ".$wpTimeMachineOptionsStorage['recent_archive_path'].").</span>";
    		echo "<p><a href='javascript:void(0)' onclick='javascript:jQuery(\"#RecentInfo\").fadeSliderToggle()'>Close this</a></p>";
    		echo "</div>";
    
    	} else {
    
    		echo "<div id=\"RecentInfo\" style=\"display:none\">";
    		echo "</div>";
    
    	}
    
    	echo "<div id=\"Info\">";
    	require 'wpTimeMachineIntructions.' . wpTimeMachinePHP . '.html';
    	echo "</div>";
    
        ?>
        
        <p>

            <div class="wpTimeMachine_progress" style="display:none">

                <h2>Please wait while your files &amp; data are being archived</h2>

                <p>
                    Please, don't close this window or tab. <br /><br />
                    A confirmation will appear when the process has completed...
                </p>

            </div>

            <div class="wpTimeMachine_complete" style="display:none">

                <h2>Your archives have been completed</h2>

                <p>
                    They should now be available via your offsite provider: <br /><br />
                    <span class="offsite_name"><?php echo $offsite_name;?></span><br /><br />
                    <a href="javascript:void(0)" onclick="jQuery.modal.close()"><?php _e("Remove this message"); ?></a>
                </p>

            </div>

            <div class="wpTimeMachine_error" style="display:none">

                <h2>Your archives have NOT been completed</h2>

                <p>
                    Unfortunately more information is not available (yet).<br /><br />
                    <a href="javascript:void(0)" onclick="jQuery.modal.close()"><?php _e("Remove this message"); ?></a>
                </p>

            </div>

            <form id="wpTimeMachine_generator_form" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>&generate=1">

                <?php wp_nonce_field('wpTimeMachine_nonce'); ?>

                <fieldset class="wpTimeMachineOptions">

                <?php

            	if (wpTimeMachinePHP == 'php4') {
                    ?>

                    <label for="offsite" class="offsite">
                    Current offsite service: <strong>FTP</strong> <em> PHP4 only has support for FTP </em>
                    </label> <br />
                    <input type="hidden" name="offsite" value="ftp">

                    <?php
            	} else {
            
            		if ($offsite != "") {
                    ?>

                    <label for="offsite" class="offsite">
                    Current offsite service: <strong><?php echo $offsite_name; ?></strong>
                    &nbsp;&nbsp;<a href="javascript:void(0)" onclick="jQuery('#offsite_selections').toggle()">Change your offsite service</a>
                    </label> <br />
                    <span id="offsite_selections" style="display:none">

                    <?php
                    } else {
                    ?>

                    <label for="offsite">Select an offsite service:</label> <br />

                    <?php
                    }
                    ?>

                    <?php

                    foreach ($offsites as $provider) {
                    	echo "<input class=\"rd\" type=\"radio\" name=\"offsite\" value=\"".$provider['offsite_short']."\"> ".$provider['offsite_name']." <br />";
                    }
                    
                    ?>

                    <script type="text/javascript">
                    jQuery("input[value='<?php echo $offsite; ?>']").attr("checked", true);
                    </script>

                    <?php
                    if ($offsite != "") {
                        ?>
                        </span>
                        <?php
            		}
            
            	}
            
                ?>

                </fieldset>

                <fieldset class="wpTimeMachineOptions">

                    File format:
                    <?php
                    	if ($wpTimeMachineOptionsStorage['format'] == "zip") {
                    		echo '<strong id="format_label">.zip</strong> &nbsp;&nbsp;<a href="javascript:void(0)" id="format" value="zip">'.$wpTimeMachineText['format_labels'][0].'</a>';
                    	} else {
                    		echo '<strong id="format_label">.tar.gz</strong> &nbsp;&nbsp;<a href="javascript:void(0)" id="format" value="tar">'.$wpTimeMachineText['format_labels'][1].'</a>';
                    	}
                    ?>

                    &nbsp;&nbsp; <a href="javascript:void(0)" onclick="jQuery('#wpTimeMachine_learn_about_formats').toggle()" class="help_link"><?php _e("Learn More"); ?></a>

                    <p id="wpTimeMachine_learn_about_formats" class="help" style="display:none">

                    By default File Formats is set to generate all of your archives as ZIP (.zip) files; since that seems to be the most commonly used archive format on most 
                    operating systems (which means you probably already have the software needed to extract Zip files).  That said, tar files (or .tar.gz) provide better
                    compression and therefore take up less disk space and take less time to transfer.  

                    </p>
                
                </fieldset>

                <fieldset class="wpTimeMachineOptions">

                <?php
                	if ($wpTimeMachineOptionsStorage['use_post_pub'] == "true") {
                ?>
                    <a class="full" href="javascript:void(0)" id="use_post_pub" value="true"><?php echo $wpTimeMachineText['use_post_pub_labels'][0]; ?></a>
                    <input type="hidden" name="use_post_pub" value="true" />
                <?php
                	} else {
                ?>
                    <a class="full" href="javascript:void(0)" id="use_post_pub" value="false"><?php echo $wpTimeMachineText['use_post_pub_labels'][1]; ?></a>
                    <input type="hidden" name="use_post_pub" value="false" />
                <?php
                	}
                ?>

                    &nbsp;&nbsp; <a href="javascript:void(0)" onclick="jQuery('#wpTimeMachine_learn_about_post_pub').toggle()" class="help_link"><?php _e("Learn More"); ?></a>

                    <p id="wpTimeMachine_learn_about_post_pub" class="help" style="display:none">

                    This feature lets you take advantage of WordPress' ability to add events after a post gets published (or updated).  Using this plugin you
                    can force WordPress to start wp Time Machine every time a publish or update event occurs.  There is a considerable price worth thinking 
                    about: using this feature will add time to publishing (depending on the size of your blog it may add several seconds, or even minutes). 
                    
                    </p>

                </fieldset>
                
                <fieldset class="wpTimeMachineOptions">

                <?php
                	if ($wpTimeMachineOptionsStorage['use_timestamp_dir'] == "true") {
                ?>
                    <a class="full" href="javascript:void(0)" id="use_timestamp_dir" value="true"><?php echo $wpTimeMachineText['use_timestamp_dir_labels'][0]; ?></a>
                    <input type="hidden" name="use_timestamp_dir" value="true" />
                <?php
                	} else {
                ?>
                    <a class="full" href="javascript:void(0)" id="use_timestamp_dir" value="false"><?php echo $wpTimeMachineText['use_timestamp_dir_labels'][1]; ?></a>
                    <input type="hidden" name="use_timestamp_dir" value="false" />
                <?php
                	}
                ?>

                    &nbsp;&nbsp; <a href="javascript:void(0)" onclick="jQuery('#wpTimeMachine_learn_about_timestamps').toggle()" class="help_link"><?php _e("Learn More"); ?></a>

                    <p id="wpTimeMachine_learn_about_timestamps" class="help" style="display:none">

                    This feature adds a date to paths or files <br />(depending on which offsite service you use).<br /><br />
                    For example, if you use Dropbox, the folder the archives appear in will now look like folder-YEAR-MONTH-DAY (or wpTimeMachine-2010-04-15).
                    This is helpful if you want to have archives kept of relatively discrete changes; but can consume a lot of space.  At this time the plugin
                    makes no attempt to delete archives of this type beyond a certain date.  What you archive stays until you delete it...

                    </p>

                </fieldset>

                <fieldset class="wpTimeMachineOptions">

                <?php
                	if ($wpTimeMachineOptionsStorage['exclude_cache'] == "true") {
                ?>
                    <a class="full" href="javascript:void(0)" id="exclude_cache" value="true"><?php echo $wpTimeMachineText['exclude_cache_labels'][0]; ?></a>
                    <input type="hidden" name="exclude_cache" value="true" />
                <?php
                	} else {
                ?>
                    <a class="full" href="javascript:void(0)" id="exclude_cache" value="false"><?php echo $wpTimeMachineText['exclude_cache_labels'][1]; ?></a>
                    <input type="hidden" name="exclude_cache" value="false" />
                <?php
                	}
                ?>

                    &nbsp;&nbsp; <a href="javascript:void(0)" onclick="jQuery('#wpTimeMachine_learn_about_cache').toggle()" class="help_link"><?php _e("Learn More"); ?></a>

                    <p id="wpTimeMachine_learn_about_cache" class="help" style="display:none">

                    This feature let's you exclude a directory called "cache" which might exist in your wp-content directory.<br /><br />
                    Important: by default wp Time Machine excludes cache directories.<br /><br />
                    This helps reduce the size of your archive if you're also using a plugin like WP Super Cache, for example.

                    </p>

                </fieldset>

                <fieldset class="wpTimeMachineOptions">

                    <?php
    
                    	if ($wpTimeMachineOptionsStorage['use_log'] == "true") {
                    		echo '<a class="full" href="javascript:void(0)" id="use_log" value="true">'.$wpTimeMachineText['use_log_labels'][0].'</a> &nbsp;&nbsp;';
                    	} else {
                    		echo '<a class="full" href="javascript:void(0)" id="use_log" value="false">'.$wpTimeMachineText['use_log_labels'][1].'</a> &nbsp;&nbsp;';
                    	}
                    
                    	if ( file_exists( wpTimeMachineLog ) ) {
                    		echo '<a class="full" href="'. wpcontent_url .'/wpTimeMachine_log.txt" target="_blank">'.$wpTimeMachineText['view_log_label'].'</a> &nbsp;&nbsp;';
                    		echo '<a class="full" href="javascript:void(0)" id="clear_log">'.$wpTimeMachineText['clear_log_label'].'</a> &nbsp;&nbsp;';
                    	}
                    ?>

                    &nbsp;&nbsp; <a href="javascript:void(0)" onclick="jQuery('#wpTimeMachine_learn_about_logging').toggle()" class="help_link"><?php _e("Learn More"); ?></a>

                    <p id="wpTimeMachine_learn_about_logging" class="help" style="display:none">

                    This feature is intended to make it easier for you to get help with issues you have while using the plugin.  The log that's created is located
                    directly in your wp-content directory &amp; contains helpful but yet harmless information that would be good to share if you have problems...
                    You can read more about logging here: <a href="http://wptimemachine.com/-/troubleshooting-and-the-log-in-1-8-5/">Troubleshooting & the log, in 1.8.5</a>.
                    And if you have issues you need help with, you can paste a link to your log along with a description
                    here: <a title="Feedback" href="http://wptimemachine.com/feedback/">Feedback</a>.

                    </p>

                </fieldset>

                <fieldset class="last">

                    <label for="remote_user"><?php echo $remote_user_label; ?>: </label>
                        <?php
                        	if ($wpTimeMachineOptionsStorage['remote_user'] != "true") {
                        		$wpTimeMachine_remote_user_display = $wpTimeMachineOptionsStorage['remote_user'];
                        	} else {
                        		$wpTimeMachine_remote_user_display = "";
                        	}
                        ?>
                        <input type="text" name="remote_user" value="<?php echo $wpTimeMachine_remote_user_display; ?>" /> <br />

                    <label for="remote_pass"><?php echo $remote_pass_label; ?>: </label>
                        <input type="password" id="remote_pass" name="remote_pass" value="<?php echo $wpTimeMachineOptionsStorage['remote_pass']; ?>" />
                        <?php
                        	if ($wpTimeMachineOptionsStorage['remote_pass_storage'] == "true") {
                        ?>
                            <a href="javascript:void(0)" id="remote_pass_storage" value="true">
                                Don't save my <span class="remote_pass_label"></span>
                            </a>
                        <?php
                        	} else {
                        ?>
                            <a href="javascript:void(0)" id="remote_pass_storage" value="false">
                                Save my <span class="remote_pass_label"></span>
                            </a>
                            <?php
                        	}
                        ?>
                        <br />

                    <label for="remote_host">Remote Host: </label>
                        <input type="text" name="remote_host" value="<?php echo $wpTimeMachineOptionsStorage['remote_host']; ?>" />

                            <span id="remote_host_advice">FTP only!</span>

                    <label for="remote_path"><?php echo $remote_path_label; ?>: </label>
                        <input type="text" name="remote_path" value="<?php echo $wpTimeMachineOptionsStorage['remote_path']; ?>" />

                            <span>Optional &amp; <a href='http://wptimemachine.com/-/remote-directory-or-bucket-tips/' target='_new' class='med'>Tips</a></span> <br />

                </fieldset>

                <div class="submit">
                    <input class="sb" type="submit" id="submit_package_request" value="Generate wp Time Machine archive" />
                </div>

            </form>
            
            <div class="wpTimeMachine_footer">
            
            	<ul>            	
            	<li class="Twitter"><a href="http://twitter.com/home?status=An awesome WordPress plugin for backups: wp Time Machine: http://wpTimeMachine.com" target="_new">Share on Twitter</a></li>
            	<li class="Facebook"><a href="http://www.facebook.com/sharer.php?u=http://wpTimeMachine.com" target="_new">Share on Facebook</a></li>
            	<li class="Official"><a href="http://wpTimeMachine.com" target="_new" title="Official website for the wp Time Machine">wpTimeMachine.com</a></li>
            	</ul>
            
            </div>

        </p>
        
        <pre id="output" style="display:none">

        <?php

    	if ($_GET['generate'] == 1) {
    
    		// store start time & begin logging archive process
    
    		$start_generate = time();
    
    		wpTimeMachine_logger( $use_log,  '--- Start to generate archives...' );
    		
            @unlink( wpcontent_archive.".tar.gz" );
            @unlink( wpcontent_archive.".zip" );
    
    		// validate referer via nonce
    
    		check_admin_referer('wpTimeMachine_nonce');
    
    		// instructions text file

                include( "wpTimeMachineArchiveInstructions.php" );

    		// restoration shell script

                include( "wpTimeMachineArchiveRestorationScript.php" );
            
    		// generate archives
    
    		if ($format == "zip") {    
    			$wpcontent_archive = new Archive_Zip( wpcontent_archive.$format );    
    		} else {    
    			$wpcontent_archive = new Archive_Tar( wpcontent_archive.$format );    
    		}
    		
    		if ($wpTimeMachineOptionsStorage['exclude_cache'] == "true") {    		
                $wpTimeMachine_excluded = array ( ".", "..", "upgrade", "cache" );
    		} else {    		
                $wpTimeMachine_excluded = array ( ".", "..", "upgrade" );    		
    		}
    		
    		$wpTimeMachine_excluded[] = wpTimeMachineOptionsFile;

    		wpTimeMachine_logger( $use_log,  '---     wpTimeMachine-content-files' . $format . ', will contain: ' );
    		
            $dh  = opendir(wpcontent_dir);
            while (false !== ($filename = readdir($dh))) {
                $wp_content_files[] = $filename;
            }
    		
    		foreach ($wp_content_files as $wp_content_file) {    		
                if ( ! in_array( $wp_content_file, $wpTimeMachine_excluded ) ) {                    
                    $wpcontent_archive_files[] = wpcontent_dir . "/" .$wp_content_file;                
                    wpTimeMachine_logger( $use_log,  '---          add: wp-content/' . $wp_content_file );                    
                }     		  
    		}

    		if ($wpcontent_archive->create( $wpcontent_archive_files ) ) {
    
    			@copy( $_SERVER['DOCUMENT_ROOT'] . "/.htaccess", htaccess_archive );
    
                wpTimeMachine_logger( $use_log,  '---     wpTimeMachine-htaccess.txt (copy of .htaccess file)' );
    			
    			$instructions_handle = fopen(instructions, 'w');
    			fwrite($instructions_handle, $instructions_txt);
    			fclose($instructions_handle);
    
    			wpTimeMachine_logger( $use_log,  '---     wpTimeMachine-Instructions.txt (text instructions)' );
    
    			$restoration_handle = fopen(restoration, 'w');
    			fwrite($restoration_handle, $restoration_shell_script);
    			fclose($restoration_handle);
    
    			wpTimeMachine_logger( $use_log,  '---     wpTimeMachine-RestorationScript.sh (restoration shell script)' );

    			$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    			mysql_select_db(DB_NAME, $link);
    
    			$tables = array();
    			$result = mysql_query("SHOW TABLES");
    
    			while ($row = mysql_fetch_row($result)) {
    				$tables[] = $row[0];
    			}

    			foreach ($tables as $table) {

                    $table_prefix = substr( $table, 0, strlen(wp_table_prefix) ); 
                    // if the current $table doesn't start with the wp table prefix skip it
                    if ($table_prefix == wp_table_prefix) {    			
                    
        				$result = mysql_query('SELECT * FROM '.$table);
        				$num_fields = mysql_num_fields($result);
        
        				$wpTimeMachine_dump.= 'DROP TABLE IF EXISTS '.$table.';';
        				$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
        				$wpTimeMachine_dump.= "\n\n".$row2[1].";\n\n";
        
        				for ($i = 0; $i < $num_fields; $i++) {
        					while ($row = mysql_fetch_row($result)) {
        						$wpTimeMachine_dump.= 'INSERT INTO '.$table.' VALUES(';
        						for ($j=0; $j<$num_fields; $j++) {
        							$row[$j] = addslashes($row[$j]);
        							$row[$j] = ereg_replace("\n", "\\n", $row[$j]);
        							if (isset($row[$j])) { $wpTimeMachine_dump.= '"'.$row[$j].'"' ; } else { $wpTimeMachine_dump.= '""'; }
        							if ($j<($num_fields-1)) { $wpTimeMachine_dump.= ','; }
        						}
        						$wpTimeMachine_dump.= ");\n";
        					}
        				}
        				$wpTimeMachine_dump.="\n\n\n";
    				
    				}

    			}
    
    			$handle = fopen(wpdata_sql, 'w+');
    			fwrite($handle, $wpTimeMachine_dump);
    			fclose($handle);

    			wpTimeMachine_logger( $use_log,  '---     wpTimeMachine-data-files.sql (MySQL dump, for tables that begin with: "'. wp_table_prefix . '")' );

    		}

    		// start processing the files
    
    		wpTimeMachine_logger( $use_log,  '--- Initiate transfer to ' . $offsite );

            $POST_remote_user = $_POST['remote_user'];
            $POST_remote_pass = $_POST['remote_pass'];
            $POST_remote_path = $_POST['remote_path'];
            $POST_remote_host = $_POST['remote_host'];

            //if ($POST_remote_path != "") {
            //    
            //    if ($offsite != "ftp") {
            //        $POST_remote_path = ereg_replace("[^A-Za-z0-9-]", "", $POST_remote_path );
            //    } else {
            //        $POST_remote_path = ereg_replace("[^A-Za-z0-9-]", "", $POST_remote_path );
            //    }
            //    
            //}

    		if (wpTimeMachinePHP == 'php4') {
    			require "wpTimeMachineNonSwitch.php4";
    		} else {
    			require "wpTimeMachineSwitch.php5";
    		}
    
    		wpTimeMachine_logger( $use_log,  '--- Transfer has completed, to remote_path: ' . $remote_path );

    		// store info about this archive    
    		$wpTimeMachineOptionsStorage['recent_archive_path']     = $remote_path;
    		$wpTimeMachineOptionsStorage['recent_archive_format']   = $format;
    		$wpTimeMachineOptionsStorage['recent_archive_duration'] = time() - $wpTimeMachine_start;    
    		update_option(adminOptionsName, $wpTimeMachineOptionsStorage);
    
    		echo "<span id='update'>Your archives are ready, they took about " . $wpTimeMachineOptionsStorage['recent_archive_duration'] . " seconds to create;";
    		echo " and are in your ".$offsite_name." account (in this ".strtolower($remote_path_label).": ".$wpTimeMachineOptionsStorage['recent_archive_path'].").</span>";
    
    	}
    
        ?>

        </pre><!-- close #output -->

    </div>
	<?php
	// End plugin ui

	$elapsed = time() - $wpTimeMachine_start;

	if ($elapsed != 0) {
		wpTimeMachine_logger( $use_log,  '--- ' . $elapsed . ' seconds to execute entire process' );
	}
	unset($elapsed);

} // End function wpTimeMachine_init()

function wpTimeMachine_publish_post($offsites) 
{

    try {

        shell_exec('curl "http://wptimemachine.com/_wp_/wp-content/plugins/wp-time-machine/cron.php?generate=1"');
        
        wpTimeMachine_logger( $use_log,  '--- wpTimeMachine_publish_post: success!' );

    } catch (Exception $e) {
    
        $error = $e->getMessage();
        wpTimeMachine_logger( $use_log,  '--- wpTimeMachine_publish_post: Error: '.$error );
    }
}

function wpTimeMachine_enqueue_scripts() 
{
	
    wp_deregister_script( 'jquery' );
    wp_register_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js');
    wp_enqueue_script( 'jquery' );
    
    wp_deregister_script( 'jquery-ui-core' );
    wp_register_script( 'jquery-ui-core', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js' );
    wp_enqueue_script( 'jquery-ui-core' );
    
    wp_deregister_script( 'jquery-form' );
    wp_register_script( 'jquery-form', wpcontent_url . '/plugins/wp-time-machine/javascript/jquery.form.js' );
    wp_enqueue_script( 'jquery-form' );
    
    wp_register_script( 'jquery.validate', wpcontent_url . '/plugins/wp-time-machine/javascript/jquery.validate.js' );
    wp_enqueue_script( 'jquery.validate' );
    
    wp_register_script( 'jquery.fadeSliderToggle', wpcontent_url . '/plugins/wp-time-machine/javascript/jquery.fadeSliderToggle.js' );
    wp_enqueue_script( 'jquery.fadeSliderToggle' );
    
    wp_register_script( 'jquery.simplemodal-1.3.5.min', wpcontent_url . '/plugins/wp-time-machine/javascript/jquery.simplemodal-1.3.5.min.js' );
    wp_enqueue_script( 'jquery.simplemodal-1.3.5.min' );

}   

function wpTimeMachine_head()
{ 

    echo '<!-- wpTimeMachine_head -->                                                                                                             ' . "\n";
    echo '<link type="text/css" rel="stylesheet" href="' . wpcontent_url . '/plugins/wp-time-machine/css/wp-time-machine.css" />                  ' . "\n";
    echo '<!-- wpTimeMachine_head -->                                                                                                             ' . "\n";

}

function wpTimeMachine_logger( $use_log, $message )
{

	if ($use_log != "true") return;
	
	if (wpTimeMachine_apache_log == "true") error_log( $message );

	$log_message = "[".date("Y-m-d g:i:s a")."] " . $message ."\n";

	if ($message == "newlines") $log_message = "\n\n";

	$log_handle = fopen(wpTimeMachineLog, 'a+');
	fwrite($log_handle, $log_message);
	fclose($log_handle);

}

function wpTimeMachine_clean( $files )
{

    foreach ($files as $i => $file) {
        @unlink( $file );
    }
    
    // the only file that is $format specific is the wp-content archive
    // ...so we need to ensure that either format gets deleted 
    @unlink( wpcontent_archive.".tar.gz" );
    @unlink( wpcontent_archive.".zip" );

}

function wpTimeMachine_getAdminOptions()
{

	$wpTimeMachineOptions = unserialize(wpTimeMachineOptions);

	$wpTimeMachineOptionsStorage = get_option(adminOptionsName);
	
	$option_dump  = "";

    $option_dump .= "define( 'wpTimeMachineVersion',      '".wpTimeMachineVersion."' );     \n";
    $option_dump .= "define( 'wpcontent_url',             '".wpcontent_url."' );            \n";
    $option_dump .= "define( 'wp_install_dir',            '".wp_install_dir."' );           \n";
    $option_dump .= "define( 'wpcontent_dir',             '".wpcontent_dir."' );            \n";
    $option_dump .= "define( 'wpplugin_dir',              '".wpplugin_dir."' );             \n";
    $option_dump .= "define( 'wp_version',                '".wp_version."' );               \n";
    $option_dump .= "define( 'wp_installer_url',          '".wp_installer_url."' );         \n";
    $option_dump .= "define( 'wp_table_prefix',           '".wp_table_prefix."' );          \n";
    $option_dump .= "define( 'wpTimeMachine_apache_log',  '".wpTimeMachine_apache_log."' ); \n" . "\n\n";

	if (!empty($wpTimeMachineOptionsStorage)) {
		foreach ($wpTimeMachineOptionsStorage as $key => $option) {
			$wpTimeMachineOptions[$key] = $option;
			
			$option_dump .= "$"."wpTimeMachineOptionsStorage['" . $key . "'] = \"" . $option . "\";" . "\n";
			
		}
	}

	$option_dump = "<?php" . "\n\n" . $option_dump . "\n" . "?>";

	update_option(adminOptionsName, $wpTimeMachineOptions);

	$od_handle = fopen(wpTimeMachineOptionsFile, 'w+');
	fwrite($od_handle, $option_dump);
	fclose($od_handle);
	
	return $wpTimeMachineOptions;

}

function wpTimeMachine_admin_menu()
{
	if (function_exists('add_options_page')) {
		add_options_page('wp Time Machine', 'wp Time Machine', 9, basename(__FILE__), 'wpTimeMachine_init');
	}
}

// Deactivation function

function wpTimeMachine_deactivate()
{

	@unlink( wpTimeMachineLog );
	@unlink( wpTimeMachineOptionsFile );

	$wpTimeMachineOptions = unserialize(wpTimeMachineOptions);

	foreach ($wpTimeMachineOptions as $key => $option) {
		delete_option( $wpTimeMachineOptions[$key] );
	}

}

// Hook to kick off wp Time Machine after publish event hook fires

function wpTimeMachine_post_pub($offsites) 
{

    $wpTimeMachineOptionsStorage = wpTimeMachine_getAdminOptions();
    
    if ($wpTimeMachineOptionsStorage['use_post_pub'] == "false") {
        return;
    }

    try {

        shell_exec('curl "http://wptimemachine.com/_wp_/wp-content/plugins/wp-time-machine/cron.php?generate=1"');
        
        wpTimeMachine_logger( $use_log,  '--- wpTimeMachine_post_pub: success!' );

    } catch (Exception $e) {
    
        $error = $e->getMessage();
        
        wpTimeMachine_logger( $use_log,  '--- wpTimeMachine_post_pub: Error: '.$error );
        
    }
}

// WP Actions & Hooks

add_action('admin_enqueue_scripts', 'wpTimeMachine_enqueue_scripts');

add_action('admin_head','wpTimeMachine_head');

add_action('admin_menu', 'wpTimeMachine_admin_menu');

add_action('wp-time-machine/wp-time-machine.php', 'wpTimeMachine_init');

add_action('publish_post', 'wpTimeMachine_post_pub',1,1);

register_deactivation_hook('wp-time-machine/wp-time-machine.php', 'wpTimeMachine_deactivate');

