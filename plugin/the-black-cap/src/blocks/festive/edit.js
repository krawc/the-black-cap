import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, subtitle, intro, closeHeadline, email } = attributes;
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
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/festive"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
