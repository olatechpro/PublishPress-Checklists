<?php
/**
 * @package     PublishPress\Content_checklist
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 Open Source Training, LLC. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Content_checklist\Requirement;

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

class Featured_image extends Base_simple {
	/**
	 * The constructor. It adds the action to load the requirement.
	 *
	 * @var  string
	 *
	 * @return  void
	 */
	public function __construct( $module ) {
		$this->name = 'featured_image';

		parent::__construct( $module );
	}

	/**
	 * Initialize the language strings for the instance
	 *
	 * @return void
	 */
	public function init_language() {
		$this->lang['label']          = __( 'Featured image', 'publishpress-content-checklist' );
		$this->lang['label_settings'] = __( 'Featured image', 'publishpress-content-checklist' );
	}

	/**
	 * Returns the current status of the requirement.
	 *
	 * @param  stdClass  $post
	 * @param  mixed     $option_value
	 *
	 * @return mixed
	 */
	public function get_current_status( $post, $option_value ) {
		return ! empty( get_the_post_thumbnail( $post ) );
	}
}