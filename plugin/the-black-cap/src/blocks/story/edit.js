import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { title, copy } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Story Text', 'the-black-cap' ) } initialOpen>
					<TextControl
						label={ __( 'Title', 'the-black-cap' ) }
						value={ title }
						onChange={ ( v ) => setAttributes( { title: v } ) }
						placeholder={ __( 'Section title…', 'the-black-cap' ) }
					/>
					<TextareaControl
						label={ __( 'Copy', 'the-black-cap' ) }
						value={ copy }
						onChange={ ( v ) => setAttributes( { copy: v } ) }
						placeholder={ __( 'Story text…', 'the-black-cap' ) }
						rows={ 5 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/story"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
