<?php
/**
 * PHPStan stubs for optional integrations that are not part of the
 * WordPress stubs: Elementor (widgets/tags load only after
 * `elementor/loaded`) and WP-CLI.
 *
 * @package ZihadTravelCMS
 */

// phpcs:ignoreFile

namespace Elementor {
	class Widget_Base {
		public function __construct( $data = array(), $args = null ) {}
		/** @return array<string, mixed> */
		public function get_settings_for_display() { return array(); }
		public function start_controls_section( string $id, array $args = array() ) {}
		public function add_control( string $id, array $args = array() ) {}
		public function end_controls_section() {}
	}
	class Controls_Manager {
		public const TEXT     = 'text';
		public const TEXTAREA = 'textarea';
		public const SELECT   = 'select';
		public const NUMBER   = 'number';
		public const URL      = 'url';
	}
}

namespace Elementor\Core\DynamicTags {
	class Tag {
		public function add_control( string $id, array $args = array() ) {}
		/** @return mixed */
		public function get_settings( ?string $setting = null ) { return null; }
	}
	class Data_Tag extends Tag {}
}

namespace Elementor\Modules\DynamicTags {
	class Module {
		public const TEXT_CATEGORY  = 'text';
		public const URL_CATEGORY   = 'url';
		public const IMAGE_CATEGORY = 'image';
	}
}

namespace WP_CLI\Utils {
	/** @return object */
	function make_progress_bar( string $message, int $count ) { return new \stdClass(); }
}

namespace {
	class WP_CLI {
		public static function add_command( string $name, mixed $callable ): void {}
		public static function log( string $message ): void {}
		public static function success( string $message ): void {}
		public static function warning( string $message ): void {}
		public static function error( string $message ): void {}
		public static function line( string $message = '' ): void {}
	}

	/**
	 * The plugin kernel accessor, defined in zihad-travel-cms.php.
	 */
	function ztc(): \ZihadTravelCMS\Plugin { return \ZihadTravelCMS\Plugin::instance(); }
}
