<?php
class Stream_Papertrail_API {

	public $stream;
	public $options;

	public function __construct() {

		if ( ! class_exists( 'WP_Stream\Plugin' ) ) {
			add_action( 'admin_notices', array( $this, 'stream_not_found_notice' ) );
			return false;
		}

		$this->stream = wp_stream_get_instance();
		$this->options = $this->stream->settings->options;

		add_filter( 'wp_stream_settings_option_fields', array( $this, 'options' ) );

		if ( empty( $this->options['papertrail_destination'] ) ) {
			add_action( 'admin_notices', array( $this, 'destination_undefined_notice' ) );
		}
		else {
			add_action( 'wp_stream_record_inserted', array( $this, 'log' ), 10, 2 );
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
				array(
					'name'        => 'enable_colorization',
					'title'       => esc_html__( 'Colorization', 'stream-papertrail' ),
					'type'        => 'checkbox',
					'desc'        => esc_html__( '', 'stream-papertrail' ),
					'after_field' => esc_html__( 'Enabled', 'stream-papertrail' ),
					'default'     => 1,
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

		$destination = array_combine( array( 'hostname', 'port' ), explode( ':', $this->options['papertrail_destination'] ) );
		$program     = $this->options['papertrail_program'];
		$component   = $this->options['papertrail_component'];

		$syslog_message = '<22>' . date( 'M d H:i:s ' ) . $program . ' ' . $component . ': ' . $this->format( $message );

		$sock = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
		socket_sendto( $sock, $syslog_message, strlen( $syslog_message ), 0, $destination['hostname'], $destination['port'] );
		socket_close( $sock );

	}

	public function format( $message ) {

		if ( ! empty( $this->options['papertrail_enable_colorization'] ) && $this->options['papertrail_enable_colorization'] === 1 ) {
			$message = $this->colorise_json( $message );
		}
		return $message;

	}

	public function colorise_json( $json ) {

		$seq = array(
			'reset' => "\033[0m",
			'color' => "\033[1;%dm",
			'bold'  => "\033[1m",
		);

		$fcolor = array(
			'black'   => "\033[30m",
			'red'     => "\033[31m",
			'green'   => "\033[32m",
			'yellow'  => "\033[33m",
			'blue'    => "\033[34m",
			'magenta' => "\033[35m",
			'cyan'    => "\033[36m",
			'white'   => "\033[37m",
		);

		$bcolor = array(
			'black'   => "\033[40m",
			'red'     => "\033[41m",
			'green'   => "\033[42m",
			'yellow'  => "\033[43m",
			'blue'    => "\033[44m",
			'magenta' => "\033[45m",
			'cyan'    => "\033[46m",
			'white'   => "\033[47m",
		);

		$output = $json;
		$output = preg_replace( '/(":)([0-9]+)/', '$1' . $fcolor['magenta'] . '$2' . $seq['reset'], $output );
		$output = preg_replace( '/(":)(true|false)/', '$1' . $fcolor['magenta'] . '$2' . $seq['reset'], $output );
		$output = str_replace( '{"', '{' . $fcolor['green'] . '"', $output );
		$output = str_replace( ',"', ',' . $fcolor['green'] . '"', $output );
		$output = str_replace( '":', '"' . $seq['reset'] . ':', $output );
		$output = str_replace( ':"', ':' . $fcolor['green'] . '"', $output );
		$output = str_replace( '",', '"' . $seq['reset'] . ',', $output );
		$output = str_replace( '",', '"' . $seq['reset'] . ',', $output );
		$output = $seq['reset'] . $output . $seq['reset'];

		return $output;

	}

	public function destination_undefined_notice() {

		$class = 'error';
		$message = 'The "Stream to Papertrail" plugin requires that you set a Destination in your <a href="' . admin_url( 'admin.php?page=wp_stream_settings' ) . '">Stream Settings</a> before it can log to Papertrail.';
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';

	}

	public function stream_not_found_notice() {

		$class = 'error';
		$message = 'The "Stream to Papertrail" plugin requires the <a href="https://wordpress.org/plugins/stream/">Stream</a> plugin to be activated before it can log to Papertrail.';
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';

	}

}
