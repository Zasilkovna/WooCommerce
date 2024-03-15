/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
import { useSelect, useDispatch } from '@wordpress/data';

const { optInDefaultText } = getSetting('packeta_data', '');

const Block = ({ children, checkoutExtensionData }) => {
	const [checked, setChecked] = useState(false);
	const { setExtensionData } = checkoutExtensionData;

	const { setValidationErrors, clearValidationError } = useDispatch(
		'wc/store/validation'
	);

	useEffect(() => {
		setExtensionData('packeta', 'optin', checked);
		if (!checked) {
			setValidationErrors({
				packeta: {
					message: 'Please tick the box',
					hidden: false,
				},
			});
			return;
		}
		clearValidationError('packeta');
	}, [clearValidationError, setValidationErrors, checked, setExtensionData]);

	const { validationError } = useSelect((select) => {
		const store = select('wc/store/validation');
		return {
			validationError: store.getValidationError('packeta'),
		};
	});

	return (
		<>
			<CheckboxControl
				id="subscribe-to-newsletter"
				checked={checked}
				onChange={setChecked}
			>
				{children || optInDefaultText}
			</CheckboxControl>

			{validationError?.hidden === false && (
				<div>
					<span role="img" aria-label="Warning emoji">
						⚠️
					</span>
					{validationError?.message}
				</div>
			)}
		</>
	);
};

export default Block;
