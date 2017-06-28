<?php
/**
 * @package PublishPress
 * @author PressShack
 *
 * Copyright (c) 2017 PressShack
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use PublishPress\Addon\Content_checklist\Requirement\Base_requirement;
use PressShack\EDD_License\Updater;
use PressShack\EDD_License\License;
use PressShack\EDD_License\Setting\Field\License_key as Field_License_key;


if ( ! class_exists( 'PP_Checklist' ) ) {
	/**
	 * class PP_Checklist
	 */
	class PP_Checklist extends PP_Module {

		const METADATA_TAXONOMY     = 'pp_checklist_meta';
		const METADATA_POSTMETA_KEY = "_pp_checklist_meta";

		const SETTINGS_SLUG         = 'pp-checklist-settings';

		const POST_META_PREFIX = 'pp_checklist_custom_item_';

		public $module_name = 'checklist';

		/**
		 * List of requirements, filled with instances of requirement classes.
		 * The list is indexed by post types.
		 *
		 * @var array
		 */
		protected $requirements = array();

		/**
		 * List of post types which supports checklist
		 *
		 * @var array
		 */
		protected $post_types = array();

		/**
		 * WordPress-EDD-License-Integration
		 *
		 * @var License
		 */
		protected $license_manager;

		/**
		 * Instace for the module
		 *
		 * @var stdClass
		 */
		public $module;

		/**
		 * Construct the PP_Checklist class
		 */
		public function __construct() {
			$this->twigPath = dirname( dirname( dirname( __FILE__ ) ) ) . '/twig';

			$this->module_url = $this->get_module_url( __FILE__ );

			// Register the module with PublishPress
			$args = array(
				'title'                => __( 'Checklist', 'publishpress-content-checklist' ),
				'short_description'    => __( 'Define tasks that must be complete before content is published', 'publishpress-content-checklist' ),
				'extended_description' => __( 'Define tasks that must be complete before content is published', 'publishpress-content-checklist' ),
				'module_url'           => $this->module_url,
				'icon_class'           => 'dashicons dashicons-feedback',
				'slug'                 => 'checklist',
				'default_options'      => array(
					'enabled'                  => 'on',
					'post_types'               => array( 'post' ),
					'show_warning_icon_submit' => 'no',
					'hide_publish_button'      => 'no',
					'custom_items'             => array( 'global' => array() ),
				),
				'configure_page_cb' => 'print_configure_view',
				'options_page'      => true,
			);

			// Apply a filter to the default options
			$args['default_options'] = apply_filters( 'pp_checklist_requirements_default_options', $args['default_options'] );

			$this->module = PublishPress()->register_module( $this->module_name, $args );

			parent::__construct();

			$this->configure_twig();

			$this->license_manager = new License;
		}

		/**
		 * Loads the requirements for each post type
		 */
		protected function instantiate_requirement_classes() {
			$post_types = $this->get_post_types();

			foreach ( $post_types as $slug => $label ) {
				$this->instantiate_post_type_requirements( $slug );
			}
		}

		/**
		 * Instantiates the requirements for the given post type
		 *
		 * @param string $post_type
		 */
		protected function instantiate_post_type_requirements( $post_type ) {

			$req_classes = apply_filters( 'pp_checklist_post_type_requirements', array(), $post_type );

			if ( ! isset( $this->requirements[ $post_type ] ) ) {
				$this->requirements[ $post_type ] = array();
			}

			foreach ( $req_classes as $class_name ) {
				if ( class_exists( $class_name ) ) {
					// Instantiate the class
					$this->requirements[ $post_type ][] = new $class_name( $this->module, $post_type );
				}
			}

			// Instantiate custom items
			if ( isset( $this->module->options->custom_items ) && ! empty( $this->module->options->custom_items ) ) {
				foreach ( $this->module->options->custom_items as $id ) {
					$var_name = $id . '_title';

					// Check if there is an empty title to ignore
					if ( isset( $this->module->options->$var_name ) ) {
						$title = $this->module->options->$var_name;

						// It is expected to be an array
						if ( ! is_array( $title ) || empty( $title ) ) {
							continue;
						}

						if ( ! isset( $title[ $post_type ] ) ) {
							continue;
						}

						if ( isset( $title[ $post_type ] ) && empty( $title[ $post_type] ) ) {
							continue;
						}
					}

					$custom_item = new \PublishPress\Addon\Content_checklist\Requirement\Custom_item( $id, $this->module, $post_type );
					$this->requirements[ $post_type ][] = $custom_item;
				}
			}
		}

		/**
		 * Set the list of post types
		 *
		 * @param  array  $post_types
		 *
		 * @return array
		 */
		public function filter_post_types( $post_types ) {
			$list = array(
				'post' => esc_html__( 'Posts' ),
				'page' => esc_html__( 'Pages' ),
			);

			return array_merge( $post_types, $list );
		}

		/**
		 * Set the requirements list for the given post type
		 *
		 * @param  array  $requirements
		 * @param  string $post_type
		 *
		 * @return array
		 */
		public function filter_post_type_requirements( $requirements, $post_type ) {
			$classes = array();

			switch ( $post_type ) {
				case 'post':
					$classes = array(
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Categories_count',
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Tags_count',
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Words_count',
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Featured_image',
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Filled_excerpt',
					);
					break;

				case 'page':
					$classes = array(
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Words_count',
						'\\PublishPress\\Addon\\Content_checklist\\Requirement\\Featured_image',
					);
					break;
			}

			if ( ! empty( $classes ) ) {
				$requirements = array_merge( $requirements, $classes );
			}

			return $requirements;
		}

		protected function configure_twig() {
			$function = new Twig_SimpleFunction( 'settings_fields', function () {
				return settings_fields( $this->module->options_group_name );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'nonce_field', function ( $context ) {
				return wp_nonce_field( $context );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'submit_button', function () {
				return submit_button();
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( '__', function ( $id ) {
				return __( $id, 'publishpress-content-checklist' );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'do_settings_sections', function ( $section ) {
				return do_settings_sections( $section );
			} );
			$this->twig->addFunction( $function );
		}

		/**
		 * Initialize the module. Conditionally loads if the module is enabled
		 */
		public function init() {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'load_updater' ) );
			add_action( 'add_meta_boxes', array( $this, 'handle_post_metaboxes' ) );
			add_action( 'save_post', array( $this, 'save_post_metabox' ), 10, 2 );

			add_filter( 'pp_checklist_post_type_requirements', array( $this, 'filter_post_type_requirements' ), 10, 2 );
			add_filter( 'pp_checklist_post_types', array( $this, 'filter_post_types' ) );

			// Editor
			add_filter( 'mce_external_plugins', array( $this, 'add_mce_plugin' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );

			do_action( 'pp_checklist_load_addons' );

			// Load the requirements
			$this->instantiate_requirement_classes();
			do_action( 'pp_checklist_load_requirements' );
		}

		/**
		 * Load default editorial metadata the first time the module is loaded
		 *
		 * @since 0.7
		 */
		public function install() {

		}

		/**
		 * Upgrade our data in case we need to
		 *
		 * @since 0.7
		 */
		public function upgrade( $previous_version ) {

		}

		/**
		 * Generate a link to one of the editorial metadata actions
		 *
		 * @since 0.7
		 *
		 * @param array $args (optional) Action and any query args to add to the URL
		 * @return string $link Direct link to complete the action
		 */
		protected function get_link( $args = array() ) {
			$args['page']   = 'pp-modules-settings';
			$args['module'] = 'pp-checklist-settings';

			return add_query_arg( $args, get_admin_url( null, 'admin.php' ) );
		}

		/**
		 * Print the content of the configure tab.
		 */
		public function print_configure_view() {
			echo $this->twig->render(
				'settings-tab.twig',
				array(
					'form_action'        => menu_page_url( $this->module->settings_slug, false ),
					'options_group_name' => $this->module->options_group_name,
					'module_name'        => $this->module->slug,
				)
			);
		}

		/**
		 * Register settings for notifications so we can partially use the Settings API
		 * (We use the Settings API for form generation, but not saving)
		 */
		public function register_settings() {
			/**
			 *
			 * License
			 *
			 */
			add_settings_section(
				$this->module->options_group_name . '_license',
				__( 'Licensing:', 'publishpress-content-checklist' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'license_key',
				__( 'License key:', 'publishpress-content-checklist' ),
				array( $this, 'settings_license_key_option' ),
				$this->module->options_group_name,
				$this->module->options_group_name . '_license'
			);

			/**
			 *
			 * Post types
			 */

			add_settings_section(
				$this->module->options_group_name . '_post_types',
				__( 'General:', 'publishpress-content-checklist' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'post_types',
				__( 'Add to these post types:', 'publishpress-content-checklist' ),
				array( $this, 'settings_post_types_option' ),
				$this->module->options_group_name,
				$this->module->options_group_name . '_post_types'
			);

			add_settings_field(
				'show_warning_icon_submit',
				__( 'Show warning icon:', 'publishpress-content-checklist' ),
				array( $this, 'settings_show_warning_icon_submit_option' ),
				$this->module->options_group_name,
				$this->module->options_group_name . '_post_types'
			);

			add_settings_field(
				'hide_publish_button',
				__( 'Hide Publish button:', 'publishpress-content-checklist' ),
				array( $this, 'settings_hide_publish_button_option' ),
				$this->module->options_group_name,
				$this->module->options_group_name . '_post_types'
			);

			/**
			 *
			 * Requirement settings
			 */

			add_settings_section(
				$this->module->options_group_name . '_requirements',
				__( 'Requirements per Post Type:', 'publishpress-content-checklist' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'global_requirements',
				false,
				array( $this, 'settings_requirements' ),
				$this->module->options_group_name,
				$this->module->options_group_name . '_requirements',
				array(
					'post_type'   => 'global',
				)
			);
		}

		/**
		 * Displays the field to allow select the post types for checklist.
		 */
		public function settings_post_types_option() {
			global $publishpress;

			$post_types = $this->get_post_types();

			$publishpress->settings->helper_option_custom_post_type( $this->module, $post_types );
		}

		/**
		 * Displays the field to choose between display or not the warning icon
		 * close to the submit button
		 *
		 * @param  array
		 */
		public function settings_show_warning_icon_submit_option( $args = array() ) {
			$id    = $this->module->options_group_name . '_show_warning_icon_submit';
			$value = isset( $this->module->options->show_warning_icon_submit ) ? $this->module->options->show_warning_icon_submit : 'no';

			echo '<label for="' . $id . '">';
			echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[show_warning_icon_submit]" '
				. checked( $value, 'yes', false ) . ' />';
			echo '&nbsp;&nbsp;&nbsp;' . __( 'This will display a warning icon in the "Publish" box', 'publishpress-content-checklist' );
			echo '</label>';
		}

		/**
		 * Displays the field for the option of hide the submit button if the
		 * checklist is not complete.
		 *
		 * @param  array
		 */
		public function settings_hide_publish_button_option( $args = array() ) {
			$id    = $this->module->options_group_name . '_hide_publish_button';
			$value = isset( $this->module->options->hide_publish_button ) ? $this->module->options->hide_publish_button : 'no';

			echo '<label for="' . $id . '">';
			echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[hide_publish_button]" '
				. checked( $value, 'yes', false ) . ' />';
			echo '&nbsp;&nbsp;&nbsp;' . __( 'This will hide the Publish button if the checklist is not complete', 'publishpress-content-checklist' );
			echo '</label>';
		}

		/**
		 * Displays the field to choose between display or not the warning icon
		 * close to the submit button
		 *
		 * @param  array
		 */
		public function settings_license_key_option( $args = array() ) {
			$license_key    = isset( $this->module->options->license_key ) ? $this->module->options->license_key : '';
			$license_status = isset( $this->module->options->license_status ) ? $this->module->options->license_status : '';

			$field_args = array(
				'options_group_name' => $this->module->options_group_name,
				'name'               => 'license_key',
				'value'              => $license_key,
				'license_status'     => $license_status,
				'link_more_info'     => 'https://pressshack.com/publishpress/docs/activate-license',
			);
			$field = new Field_License_key( $field_args );

			echo $field;
		}

		/**
		 * Displays the table of requirements in the place of a field.
		 *
		 * @param  array  $args
		 */
		public function settings_requirements( $args = array() ) {
			// Apply filters to the list of requirements
			$post_types = $this->get_post_types();

			// Apply filters to the list of requirements
			// $requirements = apply_filters( 'pp_checklist_requirement_instances', array() );

			echo $this->twig->render(
				'settings-requirements-table.twig',
				array(
					'requirements'      => $this->requirements,
					'post_types'        => $post_types,
					'lang'              => array(
						'description'     => __( 'Description', 'publishpress-content-checklist' ),
						'params'          => __( 'Parameters', 'publishpress-content-checklist' ),
						'action'          => __( 'Action', 'publishpress-content-checklist' ),
						'add_custom_item' => __( 'Add custom item', 'publishpress-content-checklist' ),
					)
				)
			);
		}

		/**
		 * Returns a list of post types the checklist support.
		 *
		 * @return array
		 */
		public function get_post_types() {
			if ( empty( $this->post_types ) ) {
				// Apply filters to the list of requirements
				$this->post_types = apply_filters( 'pp_checklist_post_types', array() );
			}

			return $this->post_types;
		}

		/**
		 * Validate data entered by the user
		 *
		 * @param array $new_options New values that have been entered by the user
		 * @return array $new_options Form values after they've been sanitized
		 */
		public function settings_validate( $new_options ) {
			if ( ! isset( $new_options['license_key'] ) ) {
				$new_options['license_key'] = '';
			}
			$new_options['license_key']    = $this->license_manager->sanitize_license_key( $new_options['license_key'] );
			$new_options['license_status'] = $this->license_manager->validate_license_key( $new_options['license_key'], PP_CONTENT_CHECKLIST_ITEM_ID );

			// Whitelist validation for the post type options
			if ( ! isset( $new_options['post_types'] ) ) {
				$new_options['post_types'] = array();
			}

			$new_options['post_types'] = $this->clean_post_type_options(
				$new_options['post_types'],
				$this->module->post_type_support
			);

			if ( ! isset ( $new_options['show_warning_icon_submit'] ) ) {
				$new_options['show_warning_icon_submit'] = Base_requirement::VALUE_NO;
			}
			$new_options['show_warning_icon_submit'] = Base_requirement::VALUE_YES === $new_options['show_warning_icon_submit'] ? Base_requirement::VALUE_YES : Base_requirement::VALUE_NO;

			if ( ! isset ( $new_options['hide_publish_button'] ) ) {
				$new_options['hide_publish_button'] = Base_requirement::VALUE_NO;
			}
			$new_options['hide_publish_button'] = Base_requirement::VALUE_YES === $new_options['hide_publish_button'] ? Base_requirement::VALUE_YES : Base_requirement::VALUE_NO;

			$new_options = apply_filters( 'pp_checklist_validate_requirement_settings', $new_options );

			return $new_options;
		}

		/**
		 * Add the MCE plugin file to make the interface between the editor and
		 * the requirement meta box. This was the unique way that worked, making
		 * it loaded before the MCE is initialized, allowing to configure it.
		 *
		 * @param array  $plugin_array
		 */
		public function add_mce_plugin( $plugin_array ) {
			$plugin_array['pp_checklist_requirements'] =
				plugin_dir_url( PP_CONTENT_CHECKLIST_FILE )
				. 'modules/checklist/assets/js/tinymce-pp-checklist-requirements.js';

			return $plugin_array;
		}

		/**
		 * Enqueue scripts and stylesheets for the admin pages.
		 */
		public function add_admin_scripts() {
			wp_enqueue_style(
				'pp-checklist-requirements',
				$this->module_url . 'assets/css/checklist-requirements.css',
				false,
				PP_CONTENT_CHECKLIST_VERSION,
				'all'
			);

			wp_enqueue_style(
				'pp-checklist-admin',
				$this->module_url . 'assets/css/admin.css',
				false,
				PP_CONTENT_CHECKLIST_VERSION,
				'all'
			);

			wp_enqueue_script(
				'pp-checklist-admin',
				plugins_url( '/modules/checklist/assets/js/admin.js', PP_CONTENT_CHECKLIST_FILE ),
				array( 'jquery' ),
				PP_CONTENT_CHECKLIST_VERSION,
				true
			);

			$rules = array();
			$rules = apply_filters( 'pp_checklist_rules_list', $rules );

			// Get all the keys of post types, to select the first one for the JS script
			$post_types = array_keys( $this->get_post_types() );
			// Make sure we are on the first item
			reset( $post_types );

			wp_localize_script(
				'pp-checklist-admin',
				'objectL10n_checklist_admin',
				array(
					'rules'           => $rules,
					'first_post_type' => current( $post_types ),
				)
			);

			wp_enqueue_style( 'pp-remodal-default-theme' );
			wp_enqueue_script( 'pp-remodal' );
		}

		/*
		==================================
		=            Meta boxes          =
		==================================
		*/

		/**
		 * Load the post metaboxes for all of the post types that are supported
		 */
		public function handle_post_metaboxes() {
			/**

				TODO:
				- Check if there is any active requirement before display the box

			 */


			$title = __( 'Checklist', 'publishpress-content-checklist' );

			if ( current_user_can( 'manage_options' ) ) {
				// Make the metabox title include a link to edit the Editorial Metadata terms. Logic similar to how Core dashboard widgets work.
				$url = $this->get_link();

				$title .= ' <span class="postbox-title-action"><a href="' . esc_url( $url ) . '" class="edit-box open-box">' . __( 'Configure', 'publishpress-content-checklist' ) . '</a></span>';
			}

			$supported_post_types = $this->get_post_types_for_module( $this->module );

			foreach ( $supported_post_types as $post_type ) {
				add_meta_box( self::METADATA_TAXONOMY, $title, array( $this, 'display_meta_box' ), $post_type, 'side', 'high' );
			}
		}

		/**
		 * Displays HTML output for Checklist post meta box
		 *
		 * @param object $post Current post
		 */
		public function display_meta_box( $post ) {
			$requirements = array();

			// Apply filters to the list of requirements
			$requirements = apply_filters( 'pp_checklist_requirement_list', $requirements, $post );

			// Add the scripts
			if ( ! empty( $requirements ) ) {
				wp_enqueue_script(
					'pp-checklist-requirements',
					plugins_url( '/modules/checklist/assets/js/checklist-admin.js', PP_CONTENT_CHECKLIST_FILE ),
					array( 'jquery' ),
					PP_CONTENT_CHECKLIST_VERSION,
					true
				);

				wp_localize_script(
					'pp-checklist-requirements',
					'objectL10n_checklist_requirements',
					array(
						'requirements'             => $requirements,
						'msg_missed_optional'      => __( 'The following requirements are not completed yet. Are you sure you want to publish?', 'publishpress-content-checklist' ),
						'msg_missed_required'      => __( 'Please complete the following requirements before publishing:', 'publishpress-content-checklist' ),
						'msg_missed_important'     => __( 'Not required, but important: ', 'publishpress-content-checklist' ),
						'show_warning_icon_submit' => Base_requirement::VALUE_YES === $this->module->options->show_warning_icon_submit,
						'hide_publish_button'      => Base_requirement::VALUE_YES === $this->module->options->hide_publish_button,
						'title_warning_icon'       => __( 'One or more items in the checklist are not completed' ),
					)
				);
			}

			// Render the box
			echo $this->twig->render(
				'checklist-metabox.twig',
				array(
					'metadata_taxonomy' => self::METADATA_TAXONOMY,
					'requirements'      => $requirements,
					'configure_link'    => $this->get_link(),
					'nonce'             => wp_create_nonce( __FILE__ ),
					'lang'              => array(
						'to_use_checklists' => __( 'To use the checklist', 'publishpress-content-checklist' ),
						'please_choose_req' => __( 'please choose some requirements', 'publishpress-content-checklist' ),
						'required'          => __( 'Required', 'publishpress-content-checklist' ),
						'dont_publish'      => __( 'Don\'t publish', 'publishpress-content-checklist' ),
						'yes_publish'       => __( 'Yes, publish', 'publishpress-content-checklist' ),

					),
				)
			);
		}

		/**
         * Save the state of custom items.
         *
         * @param int $id Unique ID for the post being saved
         * @param object $post Post object
         */
        public function save_post_metabox( $id, $post ) {

            // Authentication checks: make sure data came from our meta box and that the current user is allowed to edit the post
            // TODO: switch to using check_admin_referrer? See core (e.g. edit.php) for usage
            if ( ! isset( $_POST[ self::METADATA_TAXONOMY . "_nonce" ] )
                || ! wp_verify_nonce( $_POST[ self::METADATA_TAXONOMY . "_nonce" ], __FILE__ ) ) {
                return $id;
            }

            if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                || ! in_array( $post->post_type, $this->get_post_types_for_module( $this->module ) )
                || $post->post_type == 'post' && !current_user_can( 'edit_post', $id )
                || $post->post_type == 'page' && !current_user_can( 'edit_page', $id ) ) {
                return $id;
            }

            // Check if we have data coming from custom items
            if ( isset( $_POST['_pp_content_checklist_custom_item'] ) ) {
            	if ( ! empty( $_POST['_pp_content_checklist_custom_item'] ) ) {
            		foreach ( $_POST['_pp_content_checklist_custom_item'] as $item_id => $value ) {
            			update_post_meta( $id, self::POST_META_PREFIX . $item_id, $value );
            		}
            	}
            }
        }

		/*=====  End of Meta boxes  ======*/

		public function load_updater() {

			$license_key    = isset( $this->module->options->license_key ) ? (string) $this->module->options->license_key : '';
			$license_status = isset( $this->module->options->license_status ) ? (string) $this->module->options->license_status : License::STATUS_MISSING;

			$args = array(
				'version'        => PP_CONTENT_CHECKLIST_VERSION,
				'license'        => $license_key,
				'license_status' => $license_status,
				'item_id'        => PP_CONTENT_CHECKLIST_ITEM_ID,
				'author'         => "PressShack"
			);

			new Updater(
				PRESSSHACK_LICENSES_API_URL,
				PP_CONTENT_CHECKLIST_FILE,
				$args
			);
		}
	}
}// End if().
