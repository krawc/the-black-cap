import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, Button } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { imageIds } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Showcase Photos', 'the-black-cap' ) } initialOpen>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => {
								const ids = Array.isArray( media )
									? media.map( ( m ) => m.id )
									: [ media.id ];
								setAttributes( { imageIds: ids } );
							} }
							allowedTypes={ [ 'image' ] }
							multiple
							gallery
							value={ imageIds }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ imageIds.length
										? `${ imageIds.length } ${ __( 'photos — Edit', 'the-black-cap' ) }`
										: __( 'Add photos', 'the-black-cap' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/showcase"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
