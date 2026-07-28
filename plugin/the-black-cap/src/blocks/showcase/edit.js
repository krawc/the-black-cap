import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit( { attributes, setAttributes } ) {
	const { imageIds } = attributes;
	const blockProps = useBlockProps( { style: { background: '#111', color: '#fff', padding: '2rem' } } );

	const images = useSelect( ( select ) => {
		return imageIds
			.map( ( id ) => select( coreStore ).getMedia( id ) )
			.filter( Boolean );
	}, [ imageIds ] );

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
				<p style={ { margin: '0 0 1rem', opacity: 0.6, fontSize: '0.85rem' } }>
					{ __( 'Photo Showcase —', 'the-black-cap' ) } { imageIds.length } { __( 'photo(s) configured', 'the-black-cap' ) }
				</p>
				{ images.length > 0 && (
					<div style={ { display: 'flex', gap: '0.5rem', overflow: 'hidden', maxWidth: '100%' } }>
						{ images.slice( 0, 7 ).map( ( img ) => (
							<img
								key={ img.id }
								src={ img.source_url }
								style={ { height: '80px', width: 'auto', objectFit: 'cover', borderRadius: '4px', flexShrink: 0 } }
								alt=""
							/>
						) ) }
						{ images.length > 7 && (
							<div style={ { height: '80px', display: 'flex', alignItems: 'center', paddingLeft: '0.5rem', opacity: 0.5, fontSize: '0.8rem' } }>
								+{ images.length - 7 } { __( 'more', 'the-black-cap' ) }
							</div>
						) }
					</div>
				) }
			</div>
		</>
	);
}
