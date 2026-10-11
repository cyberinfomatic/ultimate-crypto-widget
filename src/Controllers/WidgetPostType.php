<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\{CoinGeckoHelper, Currency, Notification};
use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use WP_Error;
use WP_Query;

	class WidgetPostType {

		private UCWPMetaBoxController $metabox;
		private UCWPMetaBoxController $shortcode_metabox;
		private UCWPMetaBoxController $preview_metabox;

		private static WidgetPostType $instance;


		public function __construct() {
//			register widget type

			//			to display the preview in the metabox
			$this->preview_metabox = new UCWPMetaBoxController('ucwp_widget_preview', 'ucwp Widget Preview', 'ucwp_widget');
			$this->preview_metabox->set('context', 'normal');
			$this->preview_metabox->set('priority', 'high');
			$this->preview_metabox->set_callback(function($post) {
//				 do the shortcode
				echo do_shortcode('[ucwp_widget id="' . $post->ID . '"]');
			});
			add_action('add_meta_boxes_ucwp_widget', [$this->preview_metabox, 'show']);

			// to display the custom fields in the metabox
			$this->metabox = new UCWPMetaBoxController('ucwp_widget', 'ucwp Widget Custom Fields', 'ucwp_widget');
			$this->metabox->set('context', 'normal');
			$this->metabox->set('priority', 'default');
			$this->metabox->enqueue_styles(plugins_url('assets/styles/metabox.css', UCWP_PLUGIN_FILE), 'ucwp-admin-metabox-css', ['ucwp-chosen-css'], '1.0.1');
			$this->metabox->enqueue_scripts(plugins_url('assets/scripts/metabox.js', UCWP_PLUGIN_FILE), 'ucwp-admin-metabox-js', ['ucwp-chosen-js'], '1.0.1');
//			to display the shortcode in the metabox
			$this->shortcode_metabox = new UCWPMetaBoxController('ucwp_widget_shortcode', 'ucwp Widget Shortcode', 'ucwp_widget');
			$this->shortcode_metabox->set('context', 'side');
			$this->shortcode_metabox->set('priority', 'high');
			$this->shortcode_metabox->enqueue_scripts(plugins_url('assets/scripts/helpers.js', UCWP_PLUGIN_FILE), 'ucwp-admin-helper-js', [], '1.0.1', false);
			$this->shortcode_metabox->set_callback(function($post) {
				echo '<div><button class="ucwp-shortcode-preview-cnt" style="width : 100%;" type="button" onclick="ucwpCopyToClipboard(\'[ucwp_widget id=&quot;' . esc_attr($post->ID) . '&quot;]\', \'.ucwp-shortcode-copy\')">
							[ucwp_widget id="' . esc_attr($post->ID) . '"]
						</button></div>
						<span class="ucwp-shortcode-copy">Click to copy</span>
				';
			});
			add_action('add_meta_boxes_ucwp_widget', [$this->shortcode_metabox, 'show']);


		}

		/**
		 * Factory for Setting up the WidgetPostType
		 * @return void
		 */
		static function load(): void {


			$widget = self::get_instance();
			$widget->add_custom_fields();
			add_action( 'init', array( $widget, 'custom_post_type' ) );
			add_action( 'add_meta_boxes_ucwp_widget', array( $widget, 'activate_custom_fields' ) );
			add_action( 'save_post_ucwp_widget', array( $widget, 'save_custom_fields' ) );
			$widget->setUpPostListPage();
		}

		function setUpPostListPage(): void {
			add_filter('manage_ucwp_widget_posts_columns', function($columns) {
				$date = $columns['date'];
				unset($columns['date']);
				$columns['shortcode'] = 'Shortcode';
				$columns['widget_type'] = 'Widget Type';
				$columns['date'] = $date;
				return $columns;
			});
			add_action('manage_ucwp_widget_posts_custom_column', function($column, $post_id) {
				if ($column === 'shortcode') {
					echo '[ucwp_widget id="' . esc_attr($post_id) . '"]';
				}
				if ($column === 'widget_type') {
					$widget = new WP_Query([
						'post_type' => 'ucwp_widget',
						'p' => $post_id
					]);
					if($widget->have_posts()) {
						$widget->the_post();
						$widget_data = $this->metabox->all_meta(get_the_ID(), 'ucwp_widget');
						$field = $this->getWidgetType($widget_data['type']);
						if($field) {
							echo esc_html($field['display_name']);
						}
					}
				}
			}, 10, 2);
		}

		static function get_instance(): WidgetPostType
		{
			if (!isset(self::$instance)) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		function get_metabox(): UCWPMetaBoxController {
			return $this->metabox;
		}

		// Register Custom Post Type

		static function custom_post_type(): WP_Error|\WP_Post_Type {
			$labels = array(
				'name'                  => _x( 'ucwp widgets', 'Post Type General Name', 'ultimate-crypto-widget' ),
				'singular_name'         => _x( 'ucwp widget', 'Post Type Singular Name', 'ultimate-crypto-widget' ),
				'menu_name'             => __( 'ucwp widgets', 'ultimate-crypto-widget' ),
				'name_admin_bar'        => __( 'ucwp widget', 'ultimate-crypto-widget' ),
				'archives'              => __( 'Item Archives', 'ultimate-crypto-widget' ),
				'attributes'            => __( 'Item Attributes', 'ultimate-crypto-widget' ),
				'parent_item_colon'     => __( 'Parent Item:', 'ultimate-crypto-widget' ),
				'all_items'             => __( 'All Items', 'ultimate-crypto-widget' ),
				'add_new_item'          => __( 'Add New Item', 'ultimate-crypto-widget' ),
				'add_new'               => __( 'Add New', 'ultimate-crypto-widget' ),
				'new_item'              => __( 'New Item', 'ultimate-crypto-widget' ),
				'edit_item'             => __( 'Edit Item', 'ultimate-crypto-widget' ),
				'update_item'           => __( 'Update Item', 'ultimate-crypto-widget' ),
				'view_item'             => __( 'View Item', 'ultimate-crypto-widget' ),
				'view_items'            => __( 'View Items', 'ultimate-crypto-widget' ),
				'search_items'          => __( 'Search Item', 'ultimate-crypto-widget' ),
				'not_found'             => __( 'Not found', 'ultimate-crypto-widget' ),
				'not_found_in_trash'    => __( 'Not found in Trash', 'ultimate-crypto-widget' ),
				'featured_image'        => __( 'Featured Image', 'ultimate-crypto-widget' ),
				'set_featured_image'    => __( 'Set featured image', 'ultimate-crypto-widget' ),
				'remove_featured_image' => __( 'Remove featured image', 'ultimate-crypto-widget' ),
				'use_featured_image'    => __( 'Use as featured image', 'ultimate-crypto-widget' ),
				'insert_into_item'      => __( /** @lang text */ 'Insert into item', 'ultimate-crypto-widget' ),
				'uploaded_to_this_item' => __( 'Uploaded to this item', 'ultimate-crypto-widget' ),
				'items_list'            => __( 'Items list', 'ultimate-crypto-widget' ),
				'items_list_navigation' => __( 'Items list navigation', 'ultimate-crypto-widget' ),
				'filter_items_list'     => __( 'Filter items list', 'ultimate-crypto-widget' ),
			);




			$args = array(
				'label'                 => __( 'ucwp widget', 'ultimate-crypto-widget' ),
				'description'           => __( 'Get latest Crypto Widget on your wordpress app', 'ultimate-crypto-widget' ),
				'labels'                => $labels,
				'supports'              => array( 'title'  ), // 'custom-fields'
				'taxonomies'            => array( '' ),
				'hierarchical'          => false,
				'public'                => false,
				'show_ui'               => true,
				'menu_position'         => (Page::getMenu('ultimate-crypto-widget') ?? [])['position'] ?? 2.2,
				'show_in_admin_bar'     => false,
				'show_in_nav_menus'     => true,
				'show_in_menu'          => false,
				'can_export'            => true,
				'has_archive'           => false,
				'rewrite'               => false,
				'exclude_from_search'   => true,
				'publicly_queryable'    => true,
				'capability_type'       => 'page',
			);
			return register_post_type( 'ucwp_widget', $args );

		}


		function add_custom_fields(): void {
			foreach (self::get_fields_with_attr() as $field) {
				$this->metabox->add_field($field['id'], $field['label'], $field['type'], $field['options'] ??[], $field['attributes'] ?? []);
			}
		}

		function activate_custom_fields(): void {
			$this->metabox->show();
		}


		function save_custom_fields( $post_id ): bool {
			try{

				$selected_widget = sanitize_text_field($_POST['ucwp_widget_type'] ?? '');
				// check if it is a valid widget type / it exists
				if(!empty($selected_widget) && !array_key_exists($selected_widget, self::WidgetTypes())) {
					throw new \Exception(esc_html__('Invalid widget type, Select a valid widget type', 'ultimate-crypto-widget'));
				}
				// check if pro or free and if selected widget is pro
				if(!empty($selected_widget) &&  self::isProWidget($selected_widget)) {
					throw new \Exception(esc_html__('Get the pro version to use this widget', 'ultimate-crypto-widget'));
				}

				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
					return false;
				}

				if (get_post_status($post_id) === 'auto-draft') {
					return false;
				}

				if(!$this->metabox->save($post_id)) {
					throw new \Exception('Error saving the custom fields');
				}
				return true;
			} catch (\Exception $e) {
				Notification::add_notification($e->getMessage(), 'error');
				return false;
			}
		}





		private function get_widget_type_options(): array {
			$all_widget_types = self::WidgetTypes();
			$options = [];
			foreach($all_widget_types as $type => $widget_type) {
				$details = [
					'label' => $widget_type['display_name'],
					'attributes' => [
						'data-view' => $widget_type['view'],
						'data-card' => $widget_type['card'],
						'data-pro' => $widget_type['pro'],

					]
				];

				if( $widget_type['pro'] ){
					$details['attributes']['disabled'] = true;
//					// darken it , make the background grey
					$details['attributes']['class'] = 'ucwp-widget-pro-disabled';
//					 if label does not have pro in it add it
					if(!str_contains($details['label'], 'Pro')) {
						$details['label'] .= ' (Pro)';
					}
				}

				$options[$type] = $details;
			}

			return $options;
		}

		private function get_fields_with_attr() : array {
			$field_as_hash = $this->fields_to_hash();
			foreach(self::WidgetTypes() as $type => $details) {
				if(isset($details['setting_params'])) {
					foreach($details['setting_params'] as $setting_param) {
						if(!isset($field_as_hash['ucwp_widget_' . $setting_param]['attributes']['ucwp-show-only'])) {
							$field_as_hash['ucwp_widget_' . $setting_param]['attributes']['ucwp-show-only'] = '';
						}
						$field_as_hash['ucwp_widget_' . $setting_param]['attributes']['ucwp-show-only'] .= ' ' . $type;
					}
				}
			}
			return array_values($field_as_hash);
		}


		/**
		 * To return the fields as hash pair
		 *
		 * @param string $use  The key of each value of the hash
		 * @param bool|string $strip If a string , would remove that string from the key
		 *
		 * @return array
		 */
		private static function fields_to_hash( string $use = 'id', bool|string $strip = false): array {
			$fields = [];
			foreach(self::fields() as $field) {
				$name = $strip ? str_replace($strip . '_', '', $field[$use]) : $field[$use];
				$fields[$name] = $field;
			}
			return $fields;
		}


		// widget type functions

		/**
		 * Retrieve all available widget types.
		 *
		 * @return array An array of widget types.
		 */
		static function WidgetTypes(): array {
			$types = [];
			foreach (Services::container()->get(WidgetCatalog::class)->all() as $type => $definition) {
				$types[$type] = [
					'display_name'   => $definition->label,
					'view'           => $definition->view,
					'card'           => $definition->card,
					// "pro" here means "cannot be used on this site": listed, but locked.
					'pro'            => !$definition->available,
					'setting_params' => $definition->settings,
				];
			}

			return $types;
		}

		/**
		 * Retrieve details of a specific widget type.
		 *
		 * @param string|null $id The ID of the widget type to retrieve details for.
		 * @return array|null Details of the widget type or null if not found.
		 */
		static function getWidgetType(string $id = null): ?array {
			$widgetTypes = self::WidgetTypes();
			if ($id) {
				return $widgetTypes[$id] ?? null;
			}
			return $widgetTypes;
		}

		/**
		 * Check if a widget type is a pro version.
		 *
		 * @param string $id The ID of the widget type to check.
		 * @return bool True if the widget type is pro, false otherwise.
		 * @throws \Exception When the widget type ID is invalid.
		 */
		static function isProWidget(string $id): bool {
			$widget = self::getWidgetType($id);
			if (!$widget) {
				throw new \Exception(sprintf(esc_html__('Invalid Widget ID (%s)', 'ultimate-crypto-widget'), esc_html($id)));
			}
			return ($widget['pro'] ?? true) === true;
		}

		/**
		 * Retrieve fields configuration for widgets.
		 *
		 * @return array An array of fields configuration.
		 */
		static function fields(): array {
			return [
				[
					'id' => 'ucwp_widget_type',
					'label' => __('Widget Type', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => self::get_instance()->get_widget_type_options(),
					'attributes' => [
						'required' => true
					]
				],
				[
					'id' => 'ucwp_widget_count',
					'label' => __('No of Coins', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => (function() {
						$opt = [];
						$options = [5, 10, 20, 50];
						$pro_only = [20, 50];
						foreach($options as $i) {
							$opt[$i] = [
								'label' => "Top ($i)",
								'attributes' => [
									((string) (in_array($i, $pro_only)) ? 'disabled' : '') => ''
								]
							];
						}
						return $opt;
					})(),
				],
				[
					'id' => 'ucwp_widget_orientation',
					'label' => __('Orientation', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => [
						'horizontal' => __('Horizontal', 'ultimate-crypto-widget'),
						'vertical' => __('Vertical', 'ultimate-crypto-widget'),
					],
				],
				[
					'id' => 'ucwp_widget_speed',
					'label' => __('Speed (ms)', 'ultimate-crypto-widget'),
					'type' => 'number',
					'attributes' => [
						'min' => 100,
						'max' => 5000,
						'step' => 100
					]
				],
				[
					'id' => 'ucwp_widget_card_width',
					'label' => __('Card Width (px)', 'ultimate-crypto-widget'),
					'type' => 'number',
					'attributes' => [
						'min' => 100,
						'max' => 500,
						'value' => 157
					]
				],
				[
					'id' => 'ucwp_widget_parent_width',
					'label' => __('Parent Width (px or %)', 'ultimate-crypto-widget'),
					'type' => 'text',
					'attributes' => [
						'min' => 100,
						'max' => 500,
						'step' => 10,
						'value' => 400
					]
				],
				[
					'id' => 'ucwp_widget_default_currency',
					'label' => __('Default Currency', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => Currency::CURRENCY_PAIR
				],
				[
					'id' => 'ucwp_widget_no_of_days',
					'label' => __('No of Days', 'ultimate-crypto-widget'),
					'type' => 'number',
					'attributes' => [
						'min' => 1,
						'max' => 365,
						'step' => 1,
						'value' => 7
					]
				],
				[
					'id' => 'ucwp_widget_data_interval',
					'label' => __('Data Interval', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => [
						'daily' => __('Daily', 'ultimate-crypto-widget'),
					],
				],
				[
					'id' => 'ucwp_widget_search_placeholder',
					'label' => __('Search Placeholder', 'ultimate-crypto-widget'),
					'type' => 'text',
					'attributes' => [
						'value' => __('Search Coin', 'ultimate-crypto-widget')
					]
				],
				[
					'id' => 'ucwp_widget_coins',
					'label' => __('Coins', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => (function() {
						try {
							$coins = CoinGeckoHelper::get_coins_with_market_data();
							if(isset($coins['status'])) {
								$coins = [];
							}
							$options = [];
	//						Debugger::var_dump($coins, true);
							foreach($coins as $coin) {
								if(!isset($coin['id'])) {
									continue;
								}
								$options[$coin['id']] = [
									'label' => esc_html($coin['name'] . " (" . $coin['symbol'] . ")"),
									'attributes' => [
										'data-symbol' => $coin['symbol'],
										'value' => $coin['id']
									]
								];
							}
							return $options;
						}catch (\Exception $e) {
							return [];
						}
					})(),
					'attributes' => [
						'multiple' => true,
						'class' => 'ucwp-chosen-select',
						'data-placeholder' => __('Select Coins', 'ultimate-crypto-widget')
					]
				],
				[
					'id' => 'ucwp_widget_currencies',
					'label' => __('Currencies', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => (function() {
						$currencies = Currency::CURRENCY_PAIR;
						$options = [];
						foreach($currencies as $currency => $symbol) {
							$options[$currency] = [
								'label' => esc_html($currency . " (" . $symbol . ")"),
								'attributes' => [
									'data-symbol' => $symbol,
									'value' => $currency
								]
							];
						}
						return $options;
					})(),
					'attributes' => [
						'multiple' => true,
						'class' => 'ucwp-chosen-select',
						'data-placeholder' => __('Select Currencies', 'ultimate-crypto-widget'),
						'data-max-selected' => 3
					]
				],
//				one to select if dark mode is on or off
				[
					'id' => 'ucwp_widget_dark_mode',
					'label' => __('Dark Mode', 'ultimate-crypto-widget'),
					'type' => 'select',
					'options' => [
						'false' => [
							'label' => __('Off', 'ultimate-crypto-widget'),
							'attributes' => [
								'data-dark-mode' => 'false'
							]
						],
						'true' => [
							'label' => __('On', 'ultimate-crypto-widget'),
							'attributes' => [
								'data-dark-mode' => 'true'
							]
						]
					]
				],
			];
		}

		/**
		 * Retrieve a specific field configuration.
		 *
		 * @param string|null $id The ID of the field to retrieve.
		 * @return array|null The field configuration or null if not found.
		 */
		static function getField(string $id = null): ?array {
			if (!$id) {
				return self::fields();
			}
			return array_filter(self::fields(), fn($d) => str_ends_with($d['id'] ?? '', $id))[0] ?? null;
		}
}




