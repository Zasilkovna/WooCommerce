import { useEffect, useState, useCallback } from 'react';

import { useSelect } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { registerPaymentMethodExtensionCallbacks } from '@woocommerce/blocks-registry';

import { usePacketaShippingRate } from './usePacketaShippingRate';
import { useOnWidgetButtonClicked } from './useOnWidgetButtonClicked';
import { useDynamicSettings } from './useDynamicSettings';
import { PacketaWidget } from './PacketaWidget';

const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;

export const View = ( { cart } ) => {
	const [ viewState, setViewState ] = useState( null );
	const { shippingRates, shippingAddress, cartItemsWeight } = cart;
	const paymentStore = useSelect( ( select ) => {
		return select( PAYMENT_STORE_KEY );
	}, [] );

	const settings = getSetting( 'packeta-widget_data' );
	const {
		carrierConfig,
		translations,
		logo,
		widgetAutoOpen,
		adminAjaxUrl,
		codPaymentMethod,
	} = settings;

	const filteredShippingRates = usePacketaShippingRate(
		shippingRates,
		carrierConfig
	);
	const { packetaShippingRate = null, chosenShippingRate = null } = filteredShippingRates || {};

	const [ dynamicSettings, setDynamicSettings, loading ] = useDynamicSettings( adminAjaxUrl );




	const rateId = chosenShippingRate?.rate_id || null;
	//////// begin

	const disablePaymentMethods = useCallback( (paymentMethods) => {
		//const paymentMethods = paymentStore.getAvailablePaymentMethods();
		console.log('paymentMethods', paymentMethods);

		// todo respond properly to rate change

		console.log('rateId', rateId);
		if(rateId === null) {
			return;
		}

		const chosenRateId = rateId.split( ':' ).pop();
		const rateCarrierConfig = carrierConfig[ chosenRateId ] || null;

		if (!paymentMethods) {
			return [];
		}
		const paymentMethodsArray = Object.entries( paymentMethods );
		return paymentMethodsArray.filter( ( method ) => {
			/* todo fix, filters out HD methods
			if(!packetaShippingRate) {
				return true;
			}
			 */
			console.log('rateCarrierConfig', rateCarrierConfig);

			if ( method[0] === codPaymentMethod && ! rateCarrierConfig.supports_cod ) {
				console.log('! rateCarrierConfig.supports_cod');
				return false;
			}

			if (
				rateCarrierConfig.hasOwnProperty( 'disallowed_checkout_payment_methods' ) &&
				rateCarrierConfig.disallowed_checkout_payment_methods.includes( method[0] )
			) {
				console.log('disallowed_checkout_payment_methods', rateCarrierConfig.disallowed_checkout_payment_methods);

				return false;
			}

			return true;
		} );
	}, [rateId] );

	registerPaymentMethodExtensionCallbacks( 'packetery-js-hooks', {
		cod: (arg) => {
			console.log('arg', arg);

			const filteredPaymentMethods = disablePaymentMethods();
			return filteredPaymentMethods.some((method) => method[0] === 'cod');
		},
		bacs: (arg) => {
			const filteredPaymentMethods = disablePaymentMethods();
			return filteredPaymentMethods.some((method) => method[0] === 'bacs');
		},
		/* todo how to do it without list of methods, using disablePaymentMethods only?
		'packetery-js-hooks': ( arg ) => {
			return disablePaymentMethods();
		},
		 */
	} );

	//////// end





	useEffect( () => {
		if ( ! dynamicSettings ) {
			return;
		}

		const activePaymentMethod = paymentStore.getActivePaymentMethod();

		let shippingSaved = false;
		let paymentSaved = false;
		if (
			( ! dynamicSettings.shippingSaved && rateId ) ||
			( ! dynamicSettings.paymentSaved && activePaymentMethod !== '' )
		) {
			if ( rateId ) {
				shippingSaved = true;
			}
			if ( activePaymentMethod !== '' ) {
				paymentSaved = true;
			}

			wp.hooks.doAction( 'packetery_save_shipping_and_payment_methods', rateId, activePaymentMethod );

			setDynamicSettings( {
				...dynamicSettings,
				shippingSaved,
				paymentSaved,
			} );
		}
	}, [ paymentStore, rateId, dynamicSettings, setDynamicSettings, wp ] );

	useEffect( () => {
		if ( ! dynamicSettings ) {
			return;
		}

		const shippingCountry = shippingAddress.country.toLowerCase()

		if ( ! dynamicSettings.lastCountry ) {
			setDynamicSettings( {
				...dynamicSettings,
				lastCountry: shippingCountry,
			} );
		} else if ( dynamicSettings.lastCountry !== shippingCountry ) {
			if ( viewState ) {
				setViewState( null );
			}
			setDynamicSettings( {
				...dynamicSettings,
				lastCountry: shippingCountry,
			} );
		}
	}, [ dynamicSettings, setDynamicSettings, viewState, setViewState, shippingAddress ] );

	const onWidgetButtonClicked = useOnWidgetButtonClicked(
		packetaShippingRate,
		settings,
		dynamicSettings,
		setViewState,
		shippingAddress,
		cartItemsWeight,
	);

	useEffect( () => {
		if (
			packetaShippingRate &&
			dynamicSettings &&
			! viewState &&
			widgetAutoOpen
		) {
			onWidgetButtonClicked();
		}
	}, [ packetaShippingRate, widgetAutoOpen, onWidgetButtonClicked ] );

	const getErrorMessage = function ( viewState ) {
		if ( viewState && viewState.pickupPoint ) {
			return null;
		} else {
			return translations.pickupPointNotChosen;
		}
	};

	const { choosePickupPoint, packeta } = translations;

	if ( ! packetaShippingRate ) {
		return null;
	}

	return (
		<PacketaWidget
			onClick={ onWidgetButtonClicked }
			buttonLabel={ choosePickupPoint }
			logoSrc={ logo }
			logoAlt={ packeta }
			info={
				viewState && viewState.pickupPoint && viewState.pickupPoint.name
			}
			loading={ loading }
			placeholderText={ translations.placeholderText }
		>
			<ValidatedTextInput
				value={
					viewState && viewState.pickupPoint ? viewState.pickupPoint.name : ''
				}
				required={ true }
				errorMessage={ getErrorMessage( viewState ) }
			/>
		</PacketaWidget>
	);
};
