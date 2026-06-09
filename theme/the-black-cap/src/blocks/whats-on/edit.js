import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextareaControl, RangeControl, Notice } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { shortcodes, limit } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem' } } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Instagram Settings', 'the-black-cap' ) } initialOpen>
					<Notice status="info" isDismissible={ false }>
						{ __( 'API credentials (Instagram Access Token + User ID) are configured in Settings → Black Cap. When set, posts are fetched automatically and the shortcodes below act as a fallback only.', 'the-black-cap' ) }
					</Notice>
					<RangeControl
						label={ __( 'Number of posts', 'the-black-cap' ) }
						value={ limit }
						min={ 1 }
						max={ 20 }
						onChange={ ( v ) => setAttributes( { limit: v } ) }
					/>
					<TextareaControl
						label={ __( 'Fallback post shortcodes', 'the-black-cap' ) }
						help={ __( 'Comma-separated Instagram post shortcodes (the code after /p/ in the URL). Used when no API token is set or the API call fails.', 'the-black-cap' ) }
						value={ shortcodes }
						onChange={ ( v ) => setAttributes( { shortcodes: v } ) }
						rows={ 4 }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div style={ { marginBottom: '1.5rem' } }>
					<h2 className="whatsOnTitle">{ __( "What's On", 'the-black-cap' ) }</h2>
				</div>
				<p style={ { color: 'rgba(255,255,255,0.45)', fontSize: '0.85rem', margin: 0 } }>
					{ shortcodes
						? `${ shortcodes.split( ',' ).filter( Boolean ).length } ${ __( 'fallback post(s) configured', 'the-black-cap' ) }`
						: __( 'No fallback shortcodes — posts will be fetched from the Instagram API.', 'the-black-cap' ) }
				</p>
			</section>
		</>
	);
}
