import metadata from './block.json';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';


// Global import
const { registerCheckoutBlock } = wc.blocksCheckout;


const Block = ({ children, checkoutExtensionData }) => {
    return (
        <div className={ 'example-fields' }>
            <ValidatedTextInput
                id="gift_message"
                type="text"
                required={false}
                className={'gift-message'}
                label={
                    'Gift Message'
                }
                value={ '' }
            />
        </div>
    )
}


const options = {
    metadata,
    component: Block
};


registerCheckoutBlock( options );
