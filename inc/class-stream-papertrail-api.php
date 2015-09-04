<?php
class Stream_Papertrail_API {

	public function __construct() {

		if ( ! defined( 'PAPERTRAIL_HOSTNAME' ) || ! defined( 'PAPERTRAIL_PORT' ) ) {
			add_action( 'admin_notices', array( $this, 'constant_undefined_notice' ) );
		}
		else {
			add_action( 'wp_stream_record_inserted', array( $this, 'log' ), 10, 2 );
			add_filter( 'wp_stream_settings_option_fields', array( $this, 'options' ) );
		}
	}

	public function options( $fields ) {

		$settings = array(
			'title' => esc_html__( 'Papertrail', 'stream-papertrail' ),
			'fields' => array(
				array(
					'name'        => 'destination',
					'title'       => esc_html__( 'Destination', 'stream-papertrail' ),
					'type'        => 'text',
					'desc'        => esc_html__( 'You can check your destination on the "Account" page of your Papertrail dashboard, under "Log Destinations". It should be in the following format: logs1.papertrailapp.com:12345', 'stream-papertrail' ),
					'default'     => '',
				),
				array(
					'name'        => 'program',
					'title'       => esc_html__( 'Program', 'stream-papertrail' ),
					'type'        => 'text',
					'desc'        => esc_html__( '', 'stream-papertrail' ),
					'default'     => 'wordpress',
				),
				array(
					'name'        => 'component',
					'title'       => esc_html__( 'Component', 'stream-papertrail' ),
					'type'        => 'text',
					'desc'        => esc_html__( '', 'stream-papertrail' ),
					'default'     => 'stream',
				),
			),
		);

		$fields['papertrail'] = $settings;

		return $fields;

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

	/**
	 * This method is thanks to Troy Davis, from the Gist located here: https://gist.github.com/troy/2220679
	 */
	public function send_remote_syslog( $message, $component = 'stream', $program = 'wordpress' ) {
		$sock = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
		$syslog_message = '<22>' . date( 'M d H:i:s ' ) . $program . ' ' . $component . ': ' . $message;
		socket_sendto( $sock, $syslog_message, strlen( $syslog_message ), 0, PAPERTRAIL_HOSTNAME, PAPERTRAIL_PORT );
		socket_close( $sock );
	}

	public function constant_undefined_notice() {
		$class = 'error';
		$message = 'The "Stream to Papertrail" plugin requires that you set the <code>PAPERTRAIL_HOSTNAME</code> and <code>PAPERTRAIL_PORT</code> constants in your <strong>wp-config.php</strong> file. You can find this information in your <a href="https://papertrailapp.com/account/destinations">Papertrail dashboard</a>. Look under "Account" -> "Log Destinations" for something like <code>logs1.papertrailapp.com:12345</code> (the part before the <code>:</code> is the hostname, and the part after is the port).';
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
	}

}
