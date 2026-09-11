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

const { ready } = await import( /* webpackIgnore: true */ '@wordpress/core-abilities' );
await ready;
const { getAbility, executeAbility } = await import( /* webpackIgnore: true */ '@wordpress/abilities' );

const ABILITY = 'wp-ai-workshop-demo/create-post-from-photo';

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
        __( 'Paste an image URL and (optionally) an angle, then generate a draft post', 'wp-ai-workshop-demo' )
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

        const ability = getAbility( ABILITY );
        if ( ! ability ) {
            updateNotice( __( 'Whoops, the create-post-from-photo Ability was not found.', 'wp-ai-workshop-demo' ), 'error' );
            return;
        }

        setIsBusy( true );
        updateNotice( __( 'Looking at your image and writing a post… this can take a moment.', 'wp-ai-workshop-demo' ), 'info' );

        try {
            const result = await executeAbility( ABILITY, {
                image_url: input.image_url,
                prompt: input.prompt,
            } );

            if ( result && result.post_id ) {
                const editUrl = `post.php?post=${ result.post_id }&action=edit`;
                updateNotice(
                    <span>
                        { ( result.message || __( 'Post created.', 'wp-ai-workshop-demo' ) ) + ' ' }
                        <a href={ editUrl }>{ __( 'Edit the draft post.', 'wp-ai-workshop-demo' ) }</a>
                    </span>,
                    'success'
                );
            } else {
                updateNotice( result && result.message ? result.message : __( 'Done.', 'wp-ai-workshop-demo' ), 'error' );
            }
        } catch ( err ) {
            updateNotice( __( 'Error during post generation. Check the console for details.', 'wp-ai-workshop-demo' ), 'error' );
            console.error( err );
        } finally {
            setIsBusy( false );
        }
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
