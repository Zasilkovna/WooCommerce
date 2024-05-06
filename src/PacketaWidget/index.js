/**
 * Block registration.
 *
 * @package Packetery
 */

import { registerBlockType } from "@wordpress/blocks";
import { registerCheckoutBlock } from "@woocommerce/blocks-checkout";

import metadata from "./block.json";
import { Edit } from "./Edit";
import { View } from "./View";
import { __ } from "@wordpress/i18n";

registerBlockType(
	metadata,
	{
		title: __( 'title', 'packeta-widget' ),
		description: __( 'description', 'packeta-widget' ),
		edit: Edit,
	}
);

registerCheckoutBlock(
	{
		metadata,
		component: View,
	}
);
