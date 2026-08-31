import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextareaControl, TextControl, Notice } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, eventIds } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Section Heading', 'the-black-cap' ) } initialOpen>
					<TextControl
						label={ __( 'Heading text', 'the-black-cap' ) }
						value={ heading }
						onChange={ ( v ) => setAttributes( { heading: v } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Eventbrite Settings', 'the-black-cap' ) } initialOpen={ false }>
					<Notice status="info" isDismissible={ false }>
						{ __( 'Your Eventbrite API token and Organisation ID are configured in Settings → Black Cap. All upcoming events for the next 12 months are shown on the calendar automatically. The fallback IDs below are only used when the API is unavailable.', 'the-black-cap' ) }
					</Notice>
					<TextareaControl
						label={ __( 'Fallback Eventbrite event IDs', 'the-black-cap' ) }
						help={ __( 'Comma-separated numeric Eventbrite event IDs (the number at the end of the event URL). Used only when no API token is configured or the API call fails.', 'the-black-cap' ) }
						value={ eventIds }
						onChange={ ( v ) => setAttributes( { eventIds: v } ) }
						rows={ 3 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/whats-on"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
