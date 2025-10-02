<?php

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

class FeatureFlagProvider {
	public function isSplitActive(): bool {
		// Enabled for all users. Method will be removed later.
		return true;
	}
}
