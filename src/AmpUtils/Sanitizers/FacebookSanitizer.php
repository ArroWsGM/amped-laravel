<?php

namespace Arrowsgm\Amped\AmpUtils\Sanitizers;

use AMP_Base_Sanitizer;
use Arrowsgm\Amped\AmpUtils\AmpDom;
use DOMElement;

/**
 * Class AMP_Iframe_Sanitizer
 *
 * Converts <iframe> tags to <amp-iframe>
 */
class FacebookSanitizer extends AMP_Base_Sanitizer {

	/**
	 * Tag.
	 *
	 * @var string HTML tag to identify and replace with AMP version.
	 *
	 * @since 0.2
	 */
	public static $tag = 'div';

	/**
	 * Default args.
	 *
	 * @var array {
	 *     Default args.
	 *
	 * @type int $width
	 * @type int $height
	 * }
	 */
	protected $DEFAULT_ARGS = [
		'width'  => 600,
		'height' => 400,
	];

	/**
	 * Get mapping of HTML selectors to the AMP component selectors which they may be converted into.
	 *
	 * @return array Mapping.
	 */
	public function get_selector_conversion_mapping() {
		return [
			'div' => [
				'amp-facebook-page',
				'amp-facebook-like',
				'amp-facebook-comments',
				'amp-facebook',
			],
		];
	}

	/**
	 * Sanitize the <iframe> elements from the HTML contained in this instance's DOMDocument.
	 *
	 * @since 0.2
	 */
	public function sanitize() {
		$nodes     = $this->dom->getElementsByTagName( self::$tag );
		$num_nodes = $nodes->length;

		if ( 0 === $num_nodes ) {
			return;
		}

		for ( $i = $num_nodes - 1; $i >= 0; $i -- ) {
			$node = $nodes->item( $i );
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$embed_type = $this->get_embed_type( $node );

			if ( null !== $embed_type ) {
				$this->create_amp_facebook_and_replace_node( $node, $embed_type );
			}
		}
	}

	/**
	 * Get embed type.
	 *
	 * @param DOMElement $node The DOMNode to adjust and replace.
	 *
	 * @return string|null Embed type or null if not detected.
	 */
	private function get_embed_type( $node ) {
		$class_attr = $node->getAttribute( 'class' );
		if ( null === $class_attr || ! $node->hasAttribute( 'data-href' ) ) {
			return null;
		}

		if ( false !== strpos( $class_attr, 'fb-post' ) ) {
			return 'post';
		}

		if ( false !== strpos( $class_attr, 'fb-video' ) ) {
			return 'video';
		}

		if ( false !== strpos( $class_attr, 'fb-page' ) ) {
			return 'page';
		}

		if ( false !== strpos( $class_attr, 'fb-like' ) ) {
			return 'like';
		}

		if ( false !== strpos( $class_attr, 'fb-comments' ) ) {
			return 'comments';
		}

		if ( false !== strpos( $class_attr, 'fb-comment-embed' ) ) {
			return 'comment';
		}

		return null;
	}

	/**
	 * Create amp-facebook and replace node.
	 *
	 * @param DOMElement $node The DOMNode to adjust and replace.
	 * @param string $embed_type Embed type.
	 */
	private function create_amp_facebook_and_replace_node( $node, $embed_type ) {

		$attributes = [
			'layout' => 'responsive',
			'width'  => $node->hasAttribute( 'data-width' ) ? $node->getAttribute( 'data-width' ) : $this->args['width'],
			'height' => $node->hasAttribute( 'data-height' ) ? $node->getAttribute( 'data-height' ) : $this->args['height'],
		];

		if ( '100%' === $attributes['width'] || 'auto' === $attributes['width'] ) {
			$attributes['layout'] = 'fixed-height';
			$attributes['width']  = 'auto';
		}

		$node->removeAttribute( 'data-width' );
		$node->removeAttribute( 'data-height' );

		foreach ( $node->attributes as $attribute ) {
			if ( 'data-' === substr( $attribute->nodeName, 0, 5 ) ) {
				$attributes[ $attribute->nodeName ] = $attribute->nodeValue;
			}
		}

		if ( 'page' === $embed_type ) {
			$amp_tag = 'amp-facebook-page';
		} elseif ( 'like' === $embed_type ) {
			$amp_tag = 'amp-facebook-like';
		} elseif ( 'comments' === $embed_type ) {
			$amp_tag = 'amp-facebook-comments';
		} else {
			$amp_tag = 'amp-facebook';

			$attributes['data-embed-as'] = $embed_type;
		}

		$amp_facebook_node = AmpDom::create_node(
			$this->dom,
			$amp_tag,
			$attributes
		);

		$fallback = null;
		foreach ( $node->childNodes as $child_node ) {
			if ( $child_node instanceof DOMElement && false !== strpos( $child_node->getAttribute( 'class' ), 'fb-xfbml-parse-ignore' ) ) {
				$fallback = $child_node;
				$child_node->parentNode->removeChild( $child_node );
				$fallback->setAttribute( 'fallback', '' );
				break;
			}
		}

		$node->parentNode->replaceChild( $amp_facebook_node, $node );
		if ( $fallback ) {
			$amp_facebook_node->appendChild( $fallback );
		}

		$this->did_convert_elements = true;
	}
}
