import { __ } from '@wordpress/i18n';
import {
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalHeading as Heading,
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalVStack as VStack,
    Button,
    Notice,
} from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';
import { DataForm } from '@wordpress/dataviews/wp';

// TODO import getAbility and executeAbility and create the ABILITY constant

const SettingsTitle = () => {
    return (
        <Heading level={ 1 }>
            { __( 'WP AI Workshop Demo — Photo to Post', 'wp-ai-workshop-demo' ) }
        </Heading>
    );
};

const GenerateButton = ( { onClick, isBusy } ) => {
    return (
        <div>
            <Button
                variant="primary"
                onClick={ onClick }
                isBusy={ isBusy }
                disabled={ isBusy }
                __next40pxDefaultSize
            >
                { __( 'Generate Post', 'wp-ai-workshop-demo' ) }
            </Button>
        </div>
    );
};

const SettingsPage = () => {

    const [ noticeStatus, setNoticeStatus ] = useState( 'info' );
    const [ noticeMessage, setNoticeMessage ] = useState(
        __( 'Ready....', 'wp-ai-workshop-demo' )
    );
    const [ isBusy, setIsBusy ] = useState( false );

    const [ input, setInput ] = useState( {
        image_url: '',
        prompt: '',
    } );

    const fields = [
        {
            id: 'image_url',
            label: __( 'Image URL', 'wp-ai-workshop-demo' ),
            type: 'text',
        },
        {
            id: 'prompt',
            label: __( 'Angle / tone (optional)', 'wp-ai-workshop-demo' ),
            type: 'text',
            Edit: 'textarea',
        },
    ];

    const generateForm = {
        fields: [ 'image_url', 'prompt' ],
    };

    // TODO add welcome message effect

    const updateNotice = ( message, status = 'info' ) => {
        setNoticeMessage( message );
        setNoticeStatus( status );
    };

    const onChange = ( edits ) => {
        setInput( ( current ) => ( {
            ...current,
            ...edits,
        } ) );
    };

    const generateFromInput = useCallback( async () => {
        if ( ! input.image_url ) {
            updateNotice( __( 'Please enter an image URL.', 'wp-ai-workshop-demo' ), 'error' );
            return;
        }

        // TODO: Use the Abilities API to execute the 'wp-ai-workshop-demo/create-post-from-photo' ability.
    }, [ input ] );

    return (
        <VStack spacing={ 4 }>
            <SettingsTitle/>
            <Notice status={ noticeStatus } isDismissible={ false }>
                { noticeMessage }
            </Notice>
            <DataForm
                data={ input }
                fields={ fields }
                form={ generateForm }
                onChange={ onChange }
            />
            <GenerateButton onClick={ generateFromInput } isBusy={ isBusy }/>
        </VStack>
    );
};

export { SettingsPage };
