<?php
/**
 * Plugin Name: Stream to Loggly
 * Plugin URI: https://github.com/japh/
 * Description: Send Stream logs to Loggly for safe-keeping.
 * Author: Japh
 * Version: 0.0.1
 * Author URI: http://japh.com.au/
 */

require_once dirname( __FILE__ ) . '/inc/class-loggly-api.php';

$stream_loggly = new Loggly_API();
