<?php
/**
 * Plugin Name: Stream to Papertrail
 * Plugin URI: https://github.com/japh/stream-papertrail
 * Description: Send Stream logs to Papertrail for safe-keeping.
 * Author: Japh
 * Version: 0.0.4
 * Author URI: http://japh.com.au/
 */

require_once dirname( __FILE__ ) . '/inc/class-stream-papertrail-api.php';

function register_stream_papertrail() {
	$stream_papertrail = new Stream_Papertrail_API();
}
add_action( 'init', 'register_stream_papertrail' );
