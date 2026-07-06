import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextareaControl, RangeControl, TextControl, Notice, RadioControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { mode, videoIds, limit, profileUrl, profileLabel } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem' } } );

	const idCount = videoIds
		? videoIds.split( ',' ).map( ( s ) => s.trim() ).filter( Boolean ).length
		: 0;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'TikTok Settings', 'the-black-cap' ) } initialOpen>
					<RadioControl
						label={ __( 'Display mode', 'the-black-cap' ) }
						selected={ mode }
						options={ [
							{ label: __( 'Thumbnails (default)', 'the-black-cap' ), value: 'thumbnail' },
							{ label: __( 'Embedded player', 'the-black-cap' ), value: 'embed' },
						] }
						onChange={ ( v ) => setAttributes( { mode: v } ) }
					/>
					<Notice status="info" isDismissible={ false }>
						{ __( 'When API credentials are set in Settings → Black Cap, the latest videos are fetched automatically. The IDs below are used as a fallback only.', 'the-black-cap' ) }
					</Notice>
					<RangeControl
						label={ __( 'Number of videos', 'the-black-cap' ) }
						value={ limit }
						min={ 1 }
						max={ 20 }
						onChange={ ( v ) => setAttributes( { limit: v } ) }
					/>
					<TextareaControl
						label={ __( 'Fallback video IDs', 'the-black-cap' ) }
						help={ __( 'Comma-separated numeric TikTok video IDs (the long number in the video URL).', 'the-black-cap' ) }
						value={ videoIds }
						onChange={ ( v ) => setAttributes( { videoIds: v } ) }
						rows={ 4 }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Profile Link', 'the-black-cap' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'TikTok profile URL', 'the-black-cap' ) }
						value={ profileUrl }
						onChange={ ( v ) => setAttributes( { profileUrl: v } ) }
						placeholder="https://www.tiktok.com/@theblackcapcamden"
					/>
					<TextControl
						label={ __( 'Button label', 'the-black-cap' ) }
						value={ profileLabel }
						onChange={ ( v ) => setAttributes( { profileLabel: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div style={ { marginBottom: '1.5rem' } }>
					<h2 className="highlightsTitle">{ __( 'The Highlights', 'the-black-cap' ) }</h2>
				</div>
				<p style={ { color: 'rgba(255,255,255,0.45)', fontSize: '0.85rem', margin: 0 } }>
					{ idCount > 0
						? `${ idCount } ${ __( 'fallback video ID(s) configured', 'the-black-cap' ) }`
						: __( 'No fallback IDs — videos will be fetched from the TikTok API.', 'the-black-cap' ) }
				</p>
				{ profileUrl && (
					<p style={ { color: 'rgba(255,255,255,0.45)', fontSize: '0.85rem', marginTop: '0.5rem' } }>
						{ __( 'Profile link:', 'the-black-cap' ) } { profileUrl }
					</p>
				) }
			</section>
		</>
	);
}
