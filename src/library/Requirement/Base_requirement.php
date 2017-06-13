<?php
/**
 * @package     PublishPress\Content_checklist
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Content_checklist\Requirement;

use PublishPress\Addon\Content_checklist\Plugin;

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

class Base_requirement {
	/**
	 * The Yes value
	 */
	const VALUE_YES = 'yes';

	/**
	 * The No value
	 */
	const VALUE_NO = 'no';

	/**
	 * The global group
	 */
	const GROUP_GLOBAL = 'global';

	/**
	 * The priority for the action to load the requirement
	 */
	const PRIORITY = 10;

	/**
	 * A reference for the current module
	 *
	 * @var PP_Checklist
	 */
	public $module;

	/**
	 * The name of the requirement, in a slug format
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Array for language strings
	 *
	 * @var array
	 */
	public $lang = array();

	/**
	 * Define if it is a custom requirement or not
	 *
	 * @var array
	 */
	public $is_custom = false;

	/**
	 * The constructor. It adds the action to load the requirement.
	 *
	 * @var  string
	 *
	 * @return  void
	 */
	public function __construct( $module ) {
		add_action( 'pp_checklist_load_requirements', array( $this, 'init' ), static::PRIORITY );

		$this->module = $module;
	}

	/**
	 * Method to initialize the Requirement, adding filters and actions to
	 * interact with the Add-on.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pp_checklist_requirements_default_options', array( $this, 'filter_default_options' ) );
		add_filter( 'pp_checklist_validate_option_group', array( $this, 'filter_settings_validate' ), 10, 2 );
		add_filter( 'pp_checklist_requirement_list', array( $this, 'filter_requirements_list' ), 10, 3 );
		add_filter( 'pp_checklist_requirement_instances', array( $this, 'filter_requirement_instances' ), 10, 4 );

		$this->init_language();
	}

	/**
	 * Initialize the language strings for the instance
	 *
	 * @return void
	 */
	public function init_language() {
		// override
	}

	/**
	 * Returns the current status of the requirement.
	 *
	 * The child class should
	 * evaluate the status and override this method.
	 *
	 * @param  stdClass  $post
	 * @param  mixed     $option_value
	 *
	 * @return mixed
	 */
	public function get_current_status( $post, $option_value ) {
		return false;
	}

	/**
	 * Add the instance of the requirement class to the list.
	 *
	 * @param  array      $requirements
	 *
	 * @return array
	 */
	public function filter_requirement_instances( $requirements ) {
		$requirements[ $this->name ] = $this;

		return $requirements;
	}

	/**
	 * Get the HTML for the title setting field.
	 *
	 * @param  string $post_type
	 *
	 * @return string
	 */
	public function get_setting_title_html( $post_type, $css_class = '' ) {
		return $this->lang['label_settings'];
	}

	/**
	 * Get the HTML for the setting field for the specific post type.
	 *
	 * @param  string $post_type
	 *
	 * @return string
	 */
	public function get_setting_field_html( $post_type, $css_class = '' ) {
		return '';
	}

	/**
	 * Get the HTML for the action list field  for the specific post type.
	 * Used for settings fields to specify if the requirement is required or
	 * not.
	 *
	 * @param  string $post_type
	 *
	 * @return string
	 */
	public function get_setting_action_list_html( $post_type ) {
		$post_type = esc_attr( $post_type );

		$option_name = $this->name . '_rule';

		$id   = "{$post_type}-{$this->module->slug}-{$option_name}";
		$name = "{$this->module->options_group_name}[{$option_name}][{$post_type}]";

		$html = sprintf(
			'<select id="%s" name="%s">',
			$id,
			$name
		);

		$rules = array();
		$rules = apply_filters( 'pp_checklist_rules_list', $rules );

		// Get the value
		$value = Plugin::RULE_DISABLED;
		if ( isset( $this->module->options->{$option_name}[ $post_type ] ) ) {
			$value = $this->module->options->{$option_name}[ $post_type ];
		}

		foreach ( $rules as $rule => $label ) {
			$html .= sprintf(
				'<option value="%s" %s>%s</option>',
				$rule,
				selected( $rule, $value, false ),
				$label
			);
		}

		$html .= '</select>';

		return $html;
	}
}
