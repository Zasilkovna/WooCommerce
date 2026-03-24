<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Order;

class LabelNoteResolver {

	/**
	 * @var array<string, callable(Order): string>
	 */
	private array $replacers = [];

	public function __construct() {
		$this->replacers['order-id'] = function ( Order $order ): string {
			$number = $order->getCustomNumberOrNumber();

			return $number ?? '';
		};
	}

	public function resolve( string $template, Order $order ): string {
		$result = $template;
		foreach ( $this->replacers as $key => $callable ) {
			$placeholder = '{{' . $key . '}}';
			$result      = str_replace( $placeholder, (string) $callable( $order ), $result );
		}

		return $result;
	}
}
