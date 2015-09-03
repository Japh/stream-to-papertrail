<?php
class Stream_Papertrail_API {

	public function __construct() {

		if ( ! defined( 'PAPERTRAIL_HOSTNAME' ) || ! defined( 'PAPERTRAIL_PORT' ) ) {
			add_action( 'admin_notices', array( $this, 'constant_undefined_notice' ) );
		}
		else {
			add_action( 'wp_stream_record_inserted', array( $this, 'log' ), 10, 2 );
		}
	}

	public function log( $record_id, $record_array ) {

		$record = $record_array;
		$record['record_id'] = $record_id;
		if ( defined( 'STREAM_PAPERTRAIL_DEV' ) ) {
			$record['development'] = true;
		}

		if ( ! empty( $record['meta']['user_meta'] ) && is_serialized( $record['meta']['user_meta'] ) ) {
			$record['meta']['user_meta'] = unserialize( $record['meta']['user_meta'] );
		}

		$this->send_remote_syslog( json_encode( $record ) );

	}

	public function send_remote_syslog( $message, $component = 'stream', $program = 'wordpress' ) {
		$sock = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
		$syslog_message = '<22>' . date( 'M d H:i:s ' ) . $program . ' ' . $component . ': ' . $message;
		socket_sendto( $sock, $syslog_message, strlen( $syslog_message ), 0, PAPERTRAIL_HOSTNAME, PAPERTRAIL_PORT );
		socket_close( $sock );
	}

	public function constant_undefined_notice() {
		$class = 'error';
		$message = 'The "Stream to Loggly" plugin requires that you set the <code>STREAM_LOGGLY_URL</code> constant in <strong>wp-config.php</strong> for it to work. You can find the value that you need to use in your Loggly dashboard. Look under "Source Setup" -> "Server Side Apps" -> "Direct from Application" -> "HTTP/S Event Endpoint".';
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
	}

}
