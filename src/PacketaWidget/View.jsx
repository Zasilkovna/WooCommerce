import { useEffect, useState } from 'react';
import { getSetting } from '@woocommerce/settings';
import { ValidatedTextInput } from '@woocommerce/blocks-components';

import { usePacketaShippingRate } from './usePacketaShippingRate';
import { useOnWidgetButtonClicked } from './useOnWidgetButtonClicked';
import { useDynamicSettings } from './useDynamicSettings';
import { PacketaWidget } from './PacketaWidget';

export const View = ( { cart } ) => {
	const [ viewState, setViewState ] = useState( null );
	const { shippingRates, cartItemsWeight } = cart;

	const settings = getSetting( 'packeta-widget_data' );
	const {
		carrierConfig,
		translations,
		logo,
		widgetAutoOpen,
		adminAjaxUrl,
	} = settings;

	const packetaShippingRate = usePacketaShippingRate(
		shippingRates,
		carrierConfig
	);

	const [ dynamicSettings, loading ] = useDynamicSettings( adminAjaxUrl, setViewState );

	const onWidgetButtonClicked = useOnWidgetButtonClicked(
		packetaShippingRate,
		settings,
		dynamicSettings,
		setViewState,
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
					viewState &&
					viewState.pickupPoint &&
					viewState.pickupPoint.name
				}
				required={ true }
				errorMessage={ getErrorMessage( viewState ) }
			/>
		</PacketaWidget>
	);
};
