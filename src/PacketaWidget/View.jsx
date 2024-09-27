import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { PacketaWidget } from './PacketaWidget';
import { useView } from "./useView";

export const View = ( { cart } ) => {

	const {
		skipView,
		buttonCallback,
		buttonLabel,
		buttonInfo,
		inputValue,
		inputRequired,
		errorMessage,
		logo,
		translations,
		loading,
	} = useView( cart );

	// translations are sometimes unexpectedly undefined
	if ( skipView || ! translations ) {
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
