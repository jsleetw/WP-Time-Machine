<?php
      
switch ($offsite) {

    case "dropbox":
    
        $uploader = new DropboxUploader( $POST_remote_user, $POST_remote_pass );
        
        wpTimeMachine_logger( $use_log,  '--- Instantiate new DropboxUploader()' );

        if ($POST_remote_path == "") {
            $dropbox_dir = "/wpTimeMachine";
        } else {
            $dropbox_dir = "/" . $POST_remote_path ;
        }
    
        if ($timestamp != "") {
            $dropbox_dir .= "-" . $timestamp;
        } 

        foreach ($files as $i => $file) {
            if (file_exists($file)) {
                $uploader->upload( $file, $remoteDir = $dropbox_dir );
            }
        }   

        $remote_path = $dropbox_dir;

        break;

    case "aws_s3":
    case "s3":
    
        if ($POST_remote_path == "") {
            $bucket = "wpTimeMachine";
        } else {
            $bucket = $POST_remote_path ;
        }
            
        if ($timestamp != "") {
            $bucket .= "-" . $timestamp;
        } 
                    
        $bucket = uniqid( $bucket . "-" );
        
        $s3 = new S3( $POST_remote_user, $POST_remote_pass );
                
        wpTimeMachine_logger( $use_log,  '--- Instantiate new S3()' );
        
        $s3->putBucket( $bucket, S3::ACL_PRIVATE );
        
        foreach ($files as $i => $file) {
        
            if (file_exists($file)) {
            
                wpTimeMachine_logger( $use_log,  '--- files loop: ' . $file . ' >> ' .  $bucket  . ' >> ' .  baseName( $file ) );
            
                $s3->putObjectFile( $file, $bucket, baseName( $file ), S3::ACL_PRIVATE );
            
            }
        }
        
        $remote_path = $bucket;

        break;

    case "ftp":

        include("wpTimeMachineNonSwitch.php4");
        
        break;
        
    default:
    
        wpTimeMachine_logger( $use_log,  '--- Error: no offsite set, or recognized.' ); 

}

?>
