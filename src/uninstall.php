<?php

/**
 * Clean up the database when the extension is uninstalled.
 *
 * @package WordPoints_Beta_Tester
 * @since 1.0.0
 */

if ( ! defined( 'WORDPOINTS_UNINSTALL_MODULE' ) ) {
	return;
}

delete_site_option( 'wordpoints_beta_version' );
