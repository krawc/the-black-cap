import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, ToggleControl, Notice } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, subtitle, intro, closeHeadline, email, showShimmer } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Festive Text', 'the-black-cap' ) } initialOpen>
					<TextControl
						label={ __( 'Heading', 'the-black-cap' ) }
						value={ heading }
						onChange={ ( v ) => setAttributes( { heading: v } ) }
					/>
					<TextControl
						label={ __( 'Subtitle', 'the-black-cap' ) }
						value={ subtitle }
						onChange={ ( v ) => setAttributes( { subtitle: v } ) }
					/>
					<TextareaControl
						label={ __( 'Intro copy (blank line = new paragraph)', 'the-black-cap' ) }
						value={ intro }
						onChange={ ( v ) => setAttributes( { intro: v } ) }
						rows={ 8 }
					/>
					<TextareaControl
						label={ __( 'Closing headline', 'the-black-cap' ) }
						value={ closeHeadline }
						onChange={ ( v ) => setAttributes( { closeHeadline: v } ) }
						rows={ 2 }
					/>
					<TextControl
						label={ __( 'Booking enquiries email', 'the-black-cap' ) }
						value={ email }
						onChange={ ( v ) => setAttributes( { email: v } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Background', 'the-black-cap' ) }>
					<ToggleControl
						label={ __( 'Shimmering sparkle background', 'the-black-cap' ) }
						help={ __( 'Animated canvas behind all three festive sections. Turn off for a plain black background.', 'the-black-cap' ) }
						checked={ !! showShimmer }
						onChange={ ( v ) => setAttributes( { showShimmer: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ showShimmer && (
					<Notice status="info" isDismissible={ false } className="tbc-editor-notice">
						{ __( 'The shimmering background is a frontend-only canvas animation and won\'t run here — sections show on their plain black background instead.', 'the-black-cap' ) }
					</Notice>
				) }
				<ServerSideRender
					block="the-black-cap/festive"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
