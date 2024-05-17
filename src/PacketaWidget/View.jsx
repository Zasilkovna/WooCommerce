import { useEffect } from 'react';
import { getSetting } from '@woocommerce/settings';
import { ValidatedTextInput } from '@woocommerce/blocks-components';

import { usePacketaShippingRate } from './usePacketaShippingRate';
import { useOnWidgetButtonClicked } from './useOnWidgetButtonClicked';
import { useDynamicSettings } from './useDynamicSettings';
import { PacketaWidget } from './PacketaWidget';

export const View = ( { cart } ) => {
	const { shippingRates } = cart;

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
	if ( ! packetaShippingRate ) {
		return null;
	}

	const [ dynamicSettings, loading ] = useDynamicSettings( adminAjaxUrl );

	const [ onWidgetButtonClicked, viewState ] = useOnWidgetButtonClicked(
		packetaShippingRate,
		settings,
		dynamicSettings
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
