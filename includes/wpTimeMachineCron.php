<?php

if (!file_exists( '../../wpTimeMachine_options.php' )) {
    //echo "The settings files called 'wpTimeMachine_options.php' is missing.";
    exit;
}

require( '../../wpTimeMachine_options.php' );  

require( $_SERVER["DOCUMENT_ROOT"] . wp_install_dir . "/wp-config.php" );  

define( 'wpcontent_archive', wpcontent_dir . "/wpTimeMachine-content-files" );
define( 'wpdata_sql',        wpcontent_dir . "/wpTimeMachine-data-files.sql" );
define( 'wpdata_sqlgz',      wpcontent_dir . "/wpTimeMachine-data-files.sql.gz" );
define( 'htaccess_archive',  wpcontent_dir . "/wpTimeMachine-htaccess.txt" );   
define( 'restoration',       wpcontent_dir . "/wpTimeMachine-RestorationScript.sh" );
define( 'instructions',      wpcontent_dir . "/wpTimeMachine-Instructions.txt" );  
define( 'wpTimeMachineLog',  wpcontent_dir . "/wpTimeMachine_log.txt" );         

$wpTimeMachineOffsites = array(

    'dropbox' => array(

        'remote_user_label' => 'Email',
        'remote_pass_label' => 'Password',
        'remote_path_label' => 'Directory',
        'offsite_name'      => 'Dropbox',
        'offsite_short'     => 'dropbox',
        'offsite'           => 'dropbox',
        
        'include'           => 'includes/DropboxUploader.php'

    ),

    's3' => array(
    
        'remote_user_label' => 'S3 Key',
        'remote_pass_label' => 'S3 Secret',
        'remote_path_label' => 'Bucket',
        'offsite_name'      => 'Amazon S3',
        'offsite_short'     => 's3',
        'offsite'           => 'aws_s3',
        
        'include'           => 'includes/S3.php'

    ),

    'ftp' => array(
    
        'remote_user_label' => 'Username',
        'remote_pass_label' => 'Password',
        'remote_path_label' => 'Directory',
        'offsite_name'      => 'FTP',
        'offsite_short'     => 'ftp',
        'offsite'           => 'ftp',

        'include'           => ''
        
    )
    
);

define( 'wpTimeMachineOffsites', serialize($wpTimeMachineOffsites) );

// Branching:
  
if (version_compare(phpversion(), '5', '>=') && function_exists("curl_version")) {
    define( 'wpTimeMachinePHP', 'php5' );
} else {
    define( 'wpTimeMachinePHP', 'php4' );
}

// Actual init call:

// current offsite provider
$offsite = $wpTimeMachineOptionsStorage['offsite'];

// 2-dimensional array with all offsite provider's metadata
$offsites = unserialize(wpTimeMachineOffsites);

if (wpTimeMachinePHP == 'php4') {
	$offsite = 'ftp';
}

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

$remote_user_label = $offsites[$offsite]['remote_user_label'];
$remote_pass_label = $offsites[$offsite]['remote_pass_label'];
$remote_path_label = $offsites[$offsite]['remote_path_label'];
$offsite_name      = $offsites[$offsite]['offsite_name'];

@unlink( wpcontent_archive.".tar.gz" );
@unlink( wpcontent_archive.".zip" );

// generate instructions text file

    include("wpTimeMachineArchiveInstructions.php");

// restoration shell script

    include("wpTimeMachineArchiveRestorationScript.php");

// generate archives

if ($format == "zip") {    
	$wpcontent_archive = new Archive_Zip( wpcontent_archive.$format );    
} else {    
	$wpcontent_archive = new Archive_Tar( wpcontent_archive.$format );    
}

if ($wpTimeMachineOptionsStorage['exclude_cache'] == "true") {    		
    $wpTimeMachine_excluded = array ( ".", "..", "cache" );
} else {    		
    $wpTimeMachine_excluded = array ( ".", ".." );    		
}

$dh  = opendir(wpcontent_dir);
while (false !== ($filename = readdir($dh))) {
    $wp_content_files[] = $filename;
}

foreach ($wp_content_files as $wp_content_file) {
    if ( ! in_array( $wp_content_file, $wpTimeMachine_excluded ) ) {
        $wpcontent_archive_files[] = wpcontent_dir . "/" .$wp_content_file;
    }
}

if ($wpcontent_archive->create( $wpcontent_archive_files ) ) {

	@copy( $_SERVER['DOCUMENT_ROOT'] . "/.htaccess", htaccess_archive );

	$instructions_handle = fopen(instructions, 'w');
	fwrite($instructions_handle, $instructions_txt);
	fclose($instructions_handle);

	$restoration_handle = fopen(restoration, 'w');
	fwrite($restoration_handle, $restoration_shell_script);
	fclose($restoration_handle);

	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $link);

	$tables = array();
	$result = mysql_query("SHOW TABLES");

	while ($row = mysql_fetch_row($result)) {
		$tables[] = $row[0];
	}

	foreach ($tables as $table) {

        $table_prefix = substr( $table, 0, strlen(wp_table_prefix) ); 
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

}

$POST_remote_user = $wpTimeMachineOptionsStorage['remote_user'];
$POST_remote_pass = $wpTimeMachineOptionsStorage['remote_pass'];
$POST_remote_path = $wpTimeMachineOptionsStorage['remote_path'];
$POST_remote_host = $wpTimeMachineOptionsStorage['remote_host'];

if (wpTimeMachinePHP == 'php4') {
	require "includes/wpTimeMachineNonSwitch.php4";
} else {
	require "includes/wpTimeMachineSwitch.php5";
}

?>
