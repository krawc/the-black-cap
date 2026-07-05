import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextareaControl, RangeControl, Notice } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { eventIds, limit } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem' } } );

	const eventCount = eventIds
		? eventIds.split( ',' ).filter( Boolean ).length
		: 0;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Eventbrite Settings', 'the-black-cap' ) } initialOpen>
					<Notice status="info" isDismissible={ false }>
						{ __( 'Your Eventbrite API token and Organisation ID are configured in Settings → Black Cap. When set, upcoming events are fetched automatically. The fallback IDs below are only used when the API is unavailable.', 'the-black-cap' ) }
					</Notice>
					<RangeControl
						label={ __( 'Max events to show', 'the-black-cap' ) }
						value={ limit }
						min={ 1 }
						max={ 20 }
						onChange={ ( v ) => setAttributes( { limit: v } ) }
					/>
					<TextareaControl
						label={ __( 'Fallback Eventbrite event IDs', 'the-black-cap' ) }
						help={ __( 'Comma-separated numeric Eventbrite event IDs (the number at the end of the event URL). Used only when no API token is configured or the API call fails.', 'the-black-cap' ) }
						value={ eventIds }
						onChange={ ( v ) => setAttributes( { eventIds: v } ) }
						rows={ 3 }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div style={ { marginBottom: '1.5rem' } }>
					<h2 className="whatsOnTitle">{ __( "What's On", 'the-black-cap' ) }</h2>
				</div>
				<p style={ { color: 'rgba(255,255,255,0.45)', fontSize: '0.85rem', margin: 0 } }>
					{ eventCount > 0
						? `${ eventCount } ${ __( 'fallback event ID(s) configured', 'the-black-cap' ) }`
						: __( 'Events will be fetched live from Eventbrite when a token is set.', 'the-black-cap' ) }
				</p>
			</section>
		</>
	);
}
