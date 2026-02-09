<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Forms\Form;

class LogFilterFormFactory {

	private WpAdapter $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * @param array<string,string> $translatedActions
	 * @param array<string,string> $translatedStatuses
	 * @param array<string,mixed>  $defaults
	 */
	public function create( array $translatedActions, array $translatedStatuses, array $defaults, string $actionUrl ): Form {
		$form = new Form();
		$form->setMethod( 'GET' );
		$form->setAction( $actionUrl );

		$form->addHidden( 'page', 'packeta-logs' );

		$orderId = $defaults['orderId'] ?? null;
		if ( $orderId !== null ) {
			$form->addHidden( 'order_id', (string) $orderId );
		}

		$form->addSelect( 'status', $this->wpAdapter->__( 'Status', 'packeta' ), $translatedStatuses )
			->setPrompt( $this->wpAdapter->__( 'All statuses', 'packeta' ) )
			->setDefaultValue( isset( $defaults['status'] ) ? (string) $defaults['status'] : '' );

		$form->addSelect( 'log_action', $this->wpAdapter->__( 'Action', 'packeta' ), $translatedActions )
			->setPrompt( $this->wpAdapter->__( 'All actions', 'packeta' ) )
			->setDefaultValue( isset( $defaults['action'] ) ? (string) $defaults['action'] : '' );

		$form->addText( 'date_from', $this->wpAdapter->__( 'Date from', 'packeta' ) )
			->setHtmlType( 'date' )
			->setDefaultValue( isset( $defaults['dateFrom'] ) ? (string) $defaults['dateFrom'] : '' );

		$form->addText( 'date_to', $this->wpAdapter->__( 'Date to', 'packeta' ) )
			->setHtmlType( 'date' )
			->setDefaultValue( isset( $defaults['dateTo'] ) ? (string) $defaults['dateTo'] : '' );

		$form->addText( 'search', $this->wpAdapter->__( 'Search in note', 'packeta' ) )
			->setDefaultValue( isset( $defaults['search'] ) ? (string) $defaults['search'] : '' );

		$form->addSubmit( 'submit', $this->wpAdapter->__( 'Filter', 'packeta' ) )
			->setHtmlAttribute( 'class', 'button button-primary' );

		return $form;
	}
}
