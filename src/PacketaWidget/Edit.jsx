import { useBlockProps } from '@wordpress/block-editor';
import { PacketaWidget } from './PacketaWidget';

export const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<PacketaWidget
				buttonLabel="Choose pickup point"
				logoSrc="/wp-content/plugins/packeta/public/packeta-symbol.png"
				logoAlt="Packeta"
				info="Pickup Point Name"
			/>
		</div>
	);
};
