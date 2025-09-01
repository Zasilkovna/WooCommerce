<?php

declare( strict_types=1 );

namespace Packetery\Module;

class Transients {
	public const CHECKOUT_DATA_PREFIX                    = 'packeta_checkout_data_';
	public const MESSAGE_MANAGER_MESSAGES_PREFIX         = 'packetery_message_manager_messages_';
	public const ORDER_COLLECTION_PRINT_ORDER_IDS_PREFIX = 'packetery_order_collection_print_order_ids_';
	public const LABEL_PRINT_ORDER_IDS_PREFIX            = 'packetery_label_print_order_ids_';
	public const LABEL_PRINT_BACK_LINK_PREFIX            = 'packetery_label_print_back_link_';
	public const METABOX_NETTE_FORM_PREV_INVALID_VALUES  = 'packetery_metabox_nette_form_prev_invalid_values';
	public const RUN_UPDATE_CARRIERS                     = 'packetery_run_update_carriers';
	public const CARRIER_CHANGES                         = 'packetery_carrier_changes';
	public const SPLIT_MESSAGE_DISMISSED                 = 'packeta_split_message_dismissed';
}
