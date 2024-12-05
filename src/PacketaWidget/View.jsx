import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { PacketaWidget } from './PacketaWidget';
import { useView } from "./useView";

export const View = ( { cart } ) => {

	const view = useView( cart );
	if ( null === view ) {
		return null;
	}

	const {
		buttonCallback,
		buttonLabel,
		buttonInfo,
		inputValue,
		inputRequired,
		errorMessage,
		logo,
		translations,
		loading,
	} = view;

	// translations are sometimes unexpectedly undefined
	if ( ! translations ) {
		return null;
	}

	return (
		<PacketaWidget
			onClick={ buttonCallback }
			buttonLabel={ buttonLabel }
			logoSrc={ logo }
			logoAlt={ translations.packeta }
			info={ buttonInfo }
			loading={ loading }
			placeholderText={ translations.placeholderText }
		>
			<ValidatedTextInput
				value={ inputValue }
				required={ inputRequired }
				errorMessage={ errorMessage }
			/>
		</PacketaWidget>
	);

};
