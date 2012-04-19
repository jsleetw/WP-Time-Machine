<?

if ($timestamp != "") {
    $remote_path = "/" . $POST_remote_path . "/wpTimeMachine-" . $timestamp;
} else {
    $remote_path = "/" . $POST_remote_path;
}

foreach ($files as $i => $file) {

    if (file_exists($file)) {

        $conn = ftp_connect( $POST_remote_host ); 
    
        wpTimeMachine_logger( $use_log,  '--- Create ftp connection ' );
    
        ftp_login( $conn, $POST_remote_user, $POST_remote_pass );
        
        @ftp_mkdir( $conn, $remote_path );
                       
        ftp_chdir( $conn, $remote_path );
    
        $remote_file = baseName( $file );
    
    	if ($POST_remote_path == "") {
            $remote_file = "/" . $remote_file;
        } else {
            $remote_file = "/" . $remote_path . "/" . $remote_file;
        }
    
        ftp_put( $conn, $remote_file, $file, FTP_BINARY );
        
        ftp_quit( $conn ); 
        
    }
    
}
        
?>
