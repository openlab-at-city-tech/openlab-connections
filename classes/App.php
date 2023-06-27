<?php
/**
 * Primary application loader.
 *
 * @package openlab-connections
 */

namespace OpenLab\Connections;

/**
 * Primary application loader.
 */
class App {
	/**
	 * Initializes the application.
	 *
	 * @return void
	 */
	public static function init() {
		/*
		$schema = Schema::get_instance();
		$schema->init();

		$editor = Editor::get_instance();
		$editor->init();

		$api = API::get_instance();
		$api->init();
		*/

		$frontend = Frontend::get_instance();
		$frontend->init();

		add_action( 'bp_init', [ __CLASS__, 'register_group_extension' ] );
	}

	/**
	 * Registers BP Group Extension.
	 *
	 * @return void
	 */
	public static function register_group_extension() {
		bp_register_group_extension( __NAMESPACE__ . '\\GroupExtension' );
	}
}
