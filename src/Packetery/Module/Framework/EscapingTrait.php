<?php

namespace Packetery\Module\Framework;

use function esc_html;

trait EscapingTrait {
	/**
	 * @param string        $url
	 * @param string[]|null $protocols
	 * @param string        $_context
	 *
	 * @return string
	 */
	public function escUrl( string $url, ?array $protocols = null, string $_context = 'display' ): string {
		return esc_url( $url, $protocols, $_context );
	}

	public function escHtml( string $inputText ): string {
		return esc_html( $inputText );
	}

	public function escAttr( string $inputText ): string {
		return esc_attr( $inputText );
	}
}
