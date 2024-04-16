import {__} from "@wordpress/i18n";
import classNames from "classnames";

export const PacketaWidget = ({
    buttonTranslationKey,
    hasError,
    logoSrc,
    message,
    onClick,
    show,
}) => {

    if (!show) {
        return null;
    }

    return <div className="packeta-widget">
        <div className="packeta-widget__button">
            <img className="packetery-widget__button-logo" src={logoSrc} alt={__('packeta-widget:packeta')}/>
            <button onClick={onClick}>{__(buttonTranslationKey)}</button>
        </div>
        <p className={classNames('packeta-widget__info', hasError && 'packeta-widget__info_error')}>
            {message}
        </p>
    </div>
}
