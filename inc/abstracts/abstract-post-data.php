<?php

/**
 * Class LP_Abstract_Post_Data
 */
class LP_Abstract_Post_Data extends LP_Abstract_Object_Data {
	/**
	 * @var string
	 */
	protected $_post_type = '';

	/**
	 * @var string
	 */
	protected $_content = '';

	/**
	 * LP_Abstract_Post_Data constructor.
	 *
	 * @param mixed $post
	 * @param array $args
	 */
	public function __construct( $post, $args = null ) {
		$id = 0;
		if ( is_numeric( $post ) ) {
			$id = absint( $post );
		} elseif ( $post instanceof LP_Abstract_Post_Data ) {
			$id = absint( $post->get_id() );
		} elseif ( isset( $post->ID ) ) {
			$id = absint( $post->ID );
		}

		settype( $args, 'array' );
		$args['id'] = $id;
		parent::__construct( $args );
	}

	/**
	 * Get status of post.
	 *
	 * @return array|mixed
	 */
	public function get_status() {
		return $this->get_data( 'status' );
	}

	/**
	 * Check if the post of this instance is exists.
	 *
	 * @return bool
	 */
	public function is_exists() {
		return get_post_type( $this->get_id() ) === $this->_post_type;
	}

	public function is_trashed() {
		return get_post_status( $this->get_id() ) === 'trash';
	}

	public function is_publish() {
		return apply_filters( 'learn-press/' . $this->_post_type . '/is-publish', get_post_status( $this->get_id() ) === 'publish' );
	}

	/**
	 * Get the title of item.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_title( $context = '' ) {
		$title = get_the_title( $this->get_id() );

		if ( 'display' === $context ) {
			$title = do_shortcode( $title );
		}

		return $title;
	}

	/**
	 * Get the content of item.
	 *
	 * @return string
	 */
	public function get_content() {

		if ( ! $this->_content ) {

			global $post, $wp_query;

			$posts = apply_filters_ref_array( 'the_posts', array( array( get_post( $this->get_id() ) ), &$wp_query ) );

			if ( $posts ) {
				$post = $posts[0];
			}

			setup_postdata( $post );

			ob_start();
			the_content();
			$this->_content = ob_get_clean();

			wp_reset_postdata();

		}

		return $this->_content;
	}

	public function get_post_status() {
		return get_post_status( $this->get_id() );
	}

	public function get_post_type() {
		return get_post_type( $this->get_id() );
	}
}