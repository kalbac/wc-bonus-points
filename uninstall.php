<?php
/**
 * Created by PhpStorm.
 * Author: Maksim Martirosov
 * Date: 13.11.2015
 * Time: 12:42
 * Project: wc-bonus-points
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_site_option( 'github_updater' );
delete_option( 'github_updater' );
delete_site_transient( 'github_updater_remote_management' );
delete_transient( 'github_updater_remote_management' );