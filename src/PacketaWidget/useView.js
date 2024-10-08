import { useEffect, useState } from 'react';

import { useSelect } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

import { usePacketaShippingRate } from "./usePacketaShippingRate";
import { useDynamicSettings } from "./useDynamicSettings";
import { useOnWidgetButtonClicked } from "./useOnWidgetButtonClicked";
import { useOnHDWidgetButtonClicked } from "./useOnHDWidgetButtonClicked";

const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;

export const useView = ( cart ) => {
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
	} = settings;

	const filteredShippingRates = usePacketaShippingRate(
		shippingRates,
		carrierConfig
	);
	const {
		packetaPickupPointShippingRate = null,
		packetaHomeDeliveryShippingRate = null,
		chosenShippingRate = null,
	} = filteredShippingRates || {};

	const [ dynamicSettings, setDynamicSettings, loading ] = useDynamicSettings( adminAjaxUrl );

	useEffect( () => {
		if ( ! dynamicSettings ) {
			return;
		}

		const activePaymentMethod = paymentStore.getActivePaymentMethod();
		const rateId = chosenShippingRate?.rate_id || null;

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
	}, [ paymentStore, chosenShippingRate, dynamicSettings, setDynamicSettings, wp ] );

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
		packetaPickupPointShippingRate,
		settings,
		dynamicSettings,
		setViewState,
		shippingAddress,
		cartItemsWeight,
	);

	const onHDWidgetButtonClicked = useOnHDWidgetButtonClicked(
		packetaHomeDeliveryShippingRate,
		settings,
		dynamicSettings,
		setViewState,
		shippingAddress,
	);

	useEffect( () => {
		if (
			packetaPickupPointShippingRate &&
			dynamicSettings &&
			! viewState &&
			widgetAutoOpen
		) {
			onWidgetButtonClicked();
		}
	}, [ packetaPickupPointShippingRate, widgetAutoOpen, onWidgetButtonClicked ] );

	const getPickupPointErrorMessage = function ( viewState ) {
		if ( viewState && viewState.pickupPoint ) {
			return null;
		} else {
			return translations.pickupPointNotChosen;
		}
	};

	const getHomeDeliveryErrorMessage = function ( viewState, addressValidationSetting ) {
		if ( addressValidationSetting === 'optional' || ( viewState && viewState.deliveryAddressInfo ) ) {
			return null;
		} else if ( viewState && viewState.deliveryAddressError ) {
			return viewState.deliveryAddressError;
		} else {
			return translations.addressIsNotValidatedAndRequiredByCarrier;
		}
	};

	let inputRequired = true;

	if ( packetaPickupPointShippingRate ) {
		return {
			buttonCallback: onWidgetButtonClicked,
			buttonLabel: translations.choosePickupPoint,
			buttonInfo: viewState && viewState.pickupPoint && viewState.pickupPoint.name,
			inputValue: viewState && viewState.pickupPoint ? viewState.pickupPoint.name : '',
			inputRequired,
			errorMessage: getPickupPointErrorMessage( viewState ),
			logo,
			translations,
			loading,
		};
	}

	if ( packetaHomeDeliveryShippingRate ) {
		const rateId = packetaHomeDeliveryShippingRate.rate_id.split( ':' ).pop();
		const rateCarrierConfig = carrierConfig[ rateId ];
		const addressValidationSetting = rateCarrierConfig.address_validation || 'none';
		if ( addressValidationSetting === 'none' ) {
			return null;
		} else if ( addressValidationSetting === 'optional' ) {
			inputRequired = false;
		}

		return {
			buttonCallback: onHDWidgetButtonClicked,
			buttonLabel: translations.chooseAddress,
			buttonInfo: viewState && viewState.deliveryAddressInfo,
			inputValue: viewState && viewState.deliveryAddressInfo ? viewState.deliveryAddressInfo : '',
			inputRequired,
			errorMessage: getHomeDeliveryErrorMessage( viewState, addressValidationSetting ),
			logo,
			translations,
			loading,
		};
	}

	return null;
};
