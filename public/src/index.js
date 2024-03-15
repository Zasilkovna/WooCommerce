import { registerBlockType } from '@wordpress/blocks';
import { SVG } from '@wordpress/components';
import { Edit } from './edit';
import metadata from './block.json';


registerBlockType(metadata, {
    icon: {
        src: (
            <SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                // Add SVG for your block icon
            </SVG>
        ),
        foreground: '#874FB9',
    },
    edit: Edit
});
