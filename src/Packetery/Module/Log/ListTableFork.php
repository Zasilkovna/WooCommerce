<?php

declare( strict_types=1 );

namespace Packetery\Module\Log;

use WP_List_Table;

class ListTableFork extends WP_List_Table {
	/**
	 * @param array<string, int> $args
	 */
	public function setPaginationArgs( array $args ): void {
		$this->set_pagination_args( $args );
	}

	/**
	 * @param 'bottom'|'top' $which
	 */
	public function renderPagination( string $which ): void {
		$this->pagination( $which );
	}
}
