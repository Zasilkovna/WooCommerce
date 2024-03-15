import {Fragment} from "react";

export const PacketaWidget = (props) => {
    const {renderOption} = props;
    console.log(props);

    const option = renderOption({});
    console.log(option);

    return <Fragment>
        <tr>
            <th>
                <img className="packetery-widget-button-logo" src={packeteryCheckoutSettings.logo}
                     alt="{$translations['packeta']}"/>
            </th>
            <td>
                <div className="packetery-widget-button-wrapper">
                    <div className="form-row packeta-widget packetery-hidden">
                        <div className="packetery-widget-button-row packeta-widget-button">
                            <img className="packetery-widget-button-logo" src={packeteryCheckoutSettings.logo}
                                 alt="{$translations['packeta']}"/>
                            <button className="button alt"> . . .</button>
                        </div>
                        <p className="packeta-widget-selected-address"></p>
                        <p className="packeta-widget-info"></p>
                    </div>
                </div>
            </td>
        </tr>
    </Fragment>
}
