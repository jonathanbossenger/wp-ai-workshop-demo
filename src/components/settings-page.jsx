import { __ } from '@wordpress/i18n';
import {
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalHeading as Heading,
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalVStack as VStack,
    Button,
    Notice,
} from '@wordpress/components';
import { useState, useEffect, useCallback } from "@wordpress/element";
import { DataForm } from '@wordpress/dataviews/wp';

import { ready } from '@wordpress/core-abilities';
import { getAbility, executeAbility } from '@wordpress/abilities';

await ready;

//const { getAbility, executeAbility } = await import( /* webpackIgnore: true */ '@wordpress/abilities' );

const SettingsTitle = () => {
    return (
        <Heading level={ 1 }>
            { __( 'WP AI Workshop Demo', 'wp-ai-workshop-demo' ) }
        </Heading>
    );
};

const GenerateButton = ( { onClick } ) => {
    return (
        <div>
            <Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
                { __( 'Generate', 'wp-ai-workshop-demo' ) }
            </Button>
        </div>
    );
};

const SettingsPage = () => {

    const [ noticeStatus, setNoticeStatus ] = useState( 'info' );
    const [ noticeMessage, setNoticeMessage ] = useState( 'Ready...' );

    const [input, setInput] = useState({
        title: "",
        prompt: "",
    });

    useEffect( () => {
        async function loadInstructionsMessage() {
            let prompt = '';
            prompt += 'A simple sentence encouraging the user to create a WordPress Post using AI. ';
            prompt += 'Only return the actual sentence. Do not include any additional text or formatting.';
            const text = await wp.aiClient.prompt(prompt).generateText();
            setNoticeMessage( text );
        }
        loadInstructionsMessage();

    }, [] );

    const fields = [
        {
            id: 'title',
            label: __( 'Title', 'wp-ai-workshop-demo' ),
            type: 'text',
        },
        {
            id: 'prompt',
            label: __( 'Prompt', 'wp-ai-workshop-demo' ),
            type: 'text',
            Edit: 'textarea',
        },
    ];

    const generateForm = {
        fields: [ 'title', 'prompt' ],
    };

    const updateNotice = ( message, status = 'info' ) => {
        setNoticeMessage( message );
        setNoticeStatus( status );
    }

    const onChange = ( edits ) => {
        setInput( ( current ) => ( {
            ...current,
            ...edits,
        } ) );
    };

    const generateFromInput = useCallback( async () => {
        const generatePostAbility = getAbility( 'wp-ai-workshop-demo/generate-post' );
        if ( ! generatePostAbility ) {
            updateNotice('Whoops, post generation Ability not found.', 'error' );
            return;
        }
        try {
            updateNotice('Attempting to execute post generation Ability, please hold for updates...', 'info' );
            const result = await executeAbility( 'wp-ai-workshop-demo/generate-post', {
                title: input.title,
                prompt: input.prompt,
            } );
            console.log(result);
        } catch ( err ) {
            updateNotice('Error during post generation. Check console for details.', 'error' );
            console.error( err );
        } finally {
            updateNotice('Post generation completed!.', 'success' );
        }
    }, [ input ] );

    return (
        <VStack spacing={ 4 }>
            <SettingsTitle/>
            <Notice status={ noticeStatus }>
                { noticeMessage }
            </Notice>
            <DataForm
                data={ input }
                fields={ fields }
                form={ generateForm }
                onChange={ onChange }
            />
            <GenerateButton onClick={ generateFromInput }/>
        </VStack>
    );
};

export { SettingsPage };
