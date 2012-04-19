<?php
/*
Plugin Name: wp Time Machine (for Backups)
Plugin URI: http://wordpress.org/extend/plugins/wp-time-machine/
Description: Creates an archive of all your WordPress Data & Files and then stores them on Dropbox, Amazon's S3, or your FTP host.
Version: 1.9.21
Author: Paul G Petty
Author URI: http://wpTimeMachine.com
*/

// wp Time Machine, Version:

    define( 'wpTimeMachineVersion', '1.9.21' );

// wp specific definitions:

    define( 'wpcontent_dir',    ( defined('WP_CONTENT_DIR') ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content' );
    define( 'wpcontent_url',    ( defined('WP_CONTENT_URL') ) ? WP_CONTENT_URL : get_option('siteurl') . '/wp-content' );
    define( 'wpplugin_dir',     ( defined('WP_PLUGIN_DIR')  ) ? WP_PLUGIN_DIR  : wpcontent_dir . '/plugins' );
    define( 'wp_install_dir',   str_replace( get_option('home'), "", get_option('siteurl') ) );
    define( 'wp_version',       $wp_version );
    define( 'wp_installer_url', 'http://wordpress.org/wordpress-'.wp_version ); // no file ext. needed, $format gets appended later   
    define( 'wp_table_prefix',  $table_prefix ); 
        
// wp Time Machine definitions:

    define('wpTimeMachine_apache_log', 'false');

    $wpTimeMachineOptions = array(

        'use_log' => 'true',
        'show_info' => 'false',
        'show_option' => 'true',
        'offsite' => 'dropbox',
        'use_timestamp_dir' => 'false',
        'format' => 'zip',
        'use_post_pub' => 'false',
        'exclude_cache' => 'true',
        'remote_user' => 'true',
        'remote_pass_stored' => 'false',
        'remote_host' => '',
        'remote_pass' => '',
        'remote_path' => '',
        'recent_archive_name' => '',
        'recent_archive_path' => '',
        'recent_archive_format' => '',
        'recent_archive_size' => '',
        'recent_archive_duration' => ''

    );

    define( 'wpTimeMachineOptions',         serialize($wpTimeMachineOptions));   
    define( 'wpcontent_archive',            wpcontent_dir . "/wpTimeMachine-content-files" );
    define( 'wpdata_sql',                   wpcontent_dir . "/wpTimeMachine-data-files.sql" );
    define( 'wpdata_sqlgz',                 wpcontent_dir . "/wpTimeMachine-data-files.sql.gz" );
    define( 'htaccess_archive',             wpcontent_dir . "/wpTimeMachine-htaccess.txt" );   
    define( 'restoration',                  wpcontent_dir . "/wpTimeMachine-RestorationScript.sh" );
    define( 'instructions',                 wpcontent_dir . "/wpTimeMachine-Instructions.txt" );  
    define( 'wpTimeMachineLog',             wpcontent_dir . "/wpTimeMachine_log.txt" );       
    define( 'wpTimeMachineOptionsFile',     wpcontent_dir . "/wpTimeMachine_options.php" );       

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

        
    $wpTimeMachineText = array(

        'show_info_labels' => array(
            "Hide Instructions",
            "Show Instructions"
        ),
    
        'use_log_labels' => array(
            "Logging: Enabled", 
            "Logging: Disabled"
        ),
        
        'view_log_label' => "View log",
        
        'clear_log_label' => "Clear log",
    
        'show_options_labels' => array(
            "Hide Plugin Options", 
            "Show Plugin Options"
        ),
    
        'format_labels' => array(
            "Use tarball (tar.gz) files",
            "Use Zip files"
        ),
    
        'use_post_pub_labels' => array(
            "Post Publish Event: Enabled", 
            "Post Publish Event: Disabled"
        ),
        
        'use_timestamp_dir_labels' => array(
            "Time-stamped Subdirectories: Enabled", 
            "Time-stamped Subdirectories: Disabled"
        ),
    
        'exclude_cache_labels' => array(
            "Cache Related Directories: Excluded", 
            "Cache Related Directories: Included"
        ),
    
        'remote_pass_storage_labels' => array(
            "Don't save my <span class='remote_pass_label'>\" + remote_pass_label + \"</span>", 
            "Save my <span class='remote_pass_label'>\" + remote_pass_label + \"</span>"
        )
    
    );
    
    define( 'wpTimeMachineText', serialize($wpTimeMachineText) );
        
// Branching:
      
    if (version_compare(phpversion(), '5', '>=') && function_exists("curl_version")) {

        define( 'wpTimeMachinePHP', 'php5' );

        require( $wpTimeMachineOffsites['dropbox']['include'] );
        require( $wpTimeMachineOffsites['s3']['include'] );
    
    } else {

        define( 'wpTimeMachinePHP', 'php4' );
        
    }

// "Universal" includes:
      
    require( 'includes/PEAR/Tar.php' );
    require( 'includes/PEAR/Zip.php' );

// The Core functionality:
 
    require( 'includes/wpTimeMachineCore.php' );

