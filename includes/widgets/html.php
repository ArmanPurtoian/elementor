<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor HTML widget.
 *
 * Elementor widget that insert a custom HTML code into the page.
 *
 * @since 1.0.0
 */
class Widget_Html extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve HTML widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'html';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve HTML widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'HTML', 'elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve HTML widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-code';
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'html', 'code', 'embed', 'script' ];
	}

	/**
	 * Register HTML widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__( 'HTML Code', 'elementor' ),
			]
		);

		$this->add_control(
			'html',
			[
				'label' => esc_html__( 'HTML Code', 'elementor' ),
				'type' => Controls_Manager::CODE,
				'default' => '',
				'placeholder' => esc_html__( 'Enter your code', 'elementor' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render HTML widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$output = $this->get_settings_for_display( 'html' );

		echo $this->sanitize_redirect_urls($output);
	}

	/**
	 * Render HTML widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 2.9.0
	 * @access protected
	 */
	protected function content_template() {
		?>
		{{{ settings.html }}}
		<?php
	}

	public function sanitize_redirect_urls( $html ) {
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$this->sanitize_attributes( $dom, [ 'data-expire-actions' ] );

		$sanitized_html = $dom->saveHTML();
		return $sanitized_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function sanitize_attributes( $dom, $attributes_to_check ) {
		foreach ( $dom->getElementsByTagName('*') as $element ) {
			foreach ( $attributes_to_check as $attribute ) {
				if ( $element->hasAttribute( $attribute ) && ! $this->is_attribute_safe( $element->getAttribute( $attribute ) ) ) {
					$element->removeAttribute( $attribute );
				}
			}
		}
	}

	private function is_attribute_safe( $value ) {
		$decoded_value = html_entity_decode( $value );
		return ! ( $this->is_json( $decoded_value ) ? ! $this->check_json( $decoded_value ) : ! $this->check_safe_value( $decoded_value ) );
	}

	private function check_json( $decoded_value ) {
		$json_array = json_decode( $decoded_value, true );

		if ( ! is_array( $json_array ) ) {
			return false;
		}

		foreach ( $json_array as $item ) {
			if ( is_array( $item ) && ! $this->check_json_item( $item ) ) {
				return false;
			}
		}
		return true;
	}

	private function check_json_item( $item ) {
		foreach ( $item as $key => $val ) {
			if ( $key === 'redirect_url' && ! $this->is_valid_url( $val ) ) {
				return false;
			}

			if ( is_string( $val ) && ! $this->check_safe_value( $val ) ) {
				return false;
			}
		}
		return true;
	}

	private function is_valid_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) ||
			   preg_match( '/^\/(\S*)$|^\.\/(\S*)$|^\.\.\/(\S*)$/', $url ) &&
			   !preg_match( '/javascript:/i', urldecode( html_entity_decode( $url ) ) );
	}

	private function check_safe_value( $val ) {
		return ! preg_match( '/(j(?:ava)?script|data:|vbscript:|<script|<iframe|on[a-z]+ *=)/i', urldecode( html_entity_decode( $val ) ) );
	}

	private function is_json( $string ) {
		json_decode( $string );

		return json_last_error() == JSON_ERROR_NONE;
	}
}
