import { useBlockProps } from '@wordpress/block-editor';
import { PacketaWidget } from "./PacketaWidget";

export const Edit = () => {
	const blockProps = useBlockProps();

	return <div { ...blockProps }>
		<PacketaWidget
			show={ true }
			buttonTranslationKey="example"
			logoSrc="/wp-content/plugins/packeta/public/packeta-symbol.png"
			message="Pickup Point Name"
		/>
	</div>

}
