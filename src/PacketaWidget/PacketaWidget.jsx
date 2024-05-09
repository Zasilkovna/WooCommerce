export const PacketaWidget = ( {
	children,
	buttonLabel,
	logoSrc,
	logoAlt,
	info,
	onClick,
	loading,
	placeholderText,
} ) => {
	return (
		<div className="packetery-widget-button-wrapper">
			{ loading && (
				<div className="packeta-widget-loading">
					{ placeholderText }
				</div>
			) }
			{ ! loading && (
				<div className="form-row packeta-widget blocks">
					<div className="packetery-widget-button-row packeta-widget-button">
						<img
							className="packetery-widget-button-logo"
							src={ logoSrc }
							alt={ logoAlt }
						/>
						<a
							onClick={ onClick }
							className="button alt components-button wc-block-components-button wp-element-button contained"
						>
							{ buttonLabel }
						</a>
					</div>
					{ children }
					{ info && <p className="packeta-widget-info">{ info }</p> }
				</div>
			) }
		</div>
	);
};
