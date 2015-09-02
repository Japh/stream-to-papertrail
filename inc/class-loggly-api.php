<?php
class Loggly_API {

	public function __construct() {

		if ( ! defined( 'STREAM_LOGGLY_URL' ) ) {
			add_action( 'admin_notices', array( $this, 'constant_undefined_notice' ) );
		}
		else {
			add_action( 'wp_stream_record_inserted', array( $this, 'log' ), 10, 2 );
		}
	}

	public function log( $record_id, $record_array ) {

		$record = $record_array;
		$record['record_id'] = $record_id;
		if ( defined( 'STREAM_LOGGLY_DEV' ) ) {
			$record['development'] = true;
		}

		if ( ! empty( $record['meta']['user_meta'] ) && is_serialized( $record['meta']['user_meta'] ) ) {
			$record['meta']['user_meta'] = unserialize( $record['meta']['user_meta'] );
		}

		$record = json_encode( $record );

		$this->request( $record );

	}

	public function request( $args = array() ) {

		$url = STREAM_LOGGLY_URL;

		$request = wp_remote_request( $url, array(
			'method' => 'POST',
			'headers' => array(
				'Content-Type' => 'text/plain'
			),
			'body' => $args,
			'blocking' => false
		));

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ) );

		return $response;

	}

	public function constant_undefined_notice() {
		$class = 'error';
		$message = 'The "Stream to Loggly" plugin requires that you set the <code>STREAM_LOGGLY_URL</code> constant in <strong>wp-config.php</strong> for it to work. You can find the value that you need to use in your Loggly dashboard. Look under "Source Setup" -> "Server Side Apps" -> "Direct from Application" -> "HTTP/S Event Endpoint".';
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
	}

}
