/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
/**
 * Internal dependencies
 */
import './style.scss';

import { registerFilters } from './filters';
import { ExampleComponent } from './ExampleComponent';

const exampleDataFromSettings = getSetting('packeta_data');

const render = () => {
	return (
		<>
			<ExperimentalOrderMeta>
				<ExampleComponent data={exampleDataFromSettings} />
			</ExperimentalOrderMeta>
		</>
	);
};

registerPlugin('packeta', {
	render,
	scope: 'woocommerce-checkout',
});

registerFilters();
