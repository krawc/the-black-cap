import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextareaControl, RangeControl, TextControl, Notice, RadioControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, mode, videoIds, limit, profileUrl, profileLabel } = attributes;
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
				<PanelBody title={ __( 'TikTok Settings', 'the-black-cap' ) } initialOpen={ false }>
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

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/highlights"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
