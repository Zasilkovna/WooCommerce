<?php

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

class FeatureFlagProvider {

	public const FLAG_SPLIT_ACTIVE  = 'splitActive';
	public const FLAG_LAST_DOWNLOAD = 'lastDownload';

	public function isSplitActive(): bool {
		// Enabled for all users. Method will be removed later.
		return true;
	}
}
