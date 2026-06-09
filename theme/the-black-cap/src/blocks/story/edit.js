import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, RichText, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, Button, RangeControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { title, copy, photos } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem 2rem 4rem' } } );

	function updatePhoto( index, key, value ) {
		const updated = photos.map( ( p, i ) => ( i === index ? { ...p, [ key ]: value } : p ) );
		setAttributes( { photos: updated } );
	}

	function removePhoto( index ) {
		setAttributes( { photos: photos.filter( ( _, i ) => i !== index ) } );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Photos & Parallax', 'the-black-cap' ) } initialOpen>
					{ photos.map( ( photo, i ) => (
						<div key={ i } style={ { marginBottom: '1.5rem', paddingBottom: '1rem', borderBottom: '1px solid #ddd' } }>
							<img
								src={ photo.url }
								style={ { width: '100%', height: '70px', objectFit: 'cover', borderRadius: '3px', marginBottom: '0.5rem' } }
								alt=""
							/>
							<RangeControl
								label={ __( 'Scale', 'the-black-cap' ) }
								value={ photo.scale }
								min={ 0.5 }
								max={ 3 }
								step={ 0.05 }
								onChange={ ( v ) => updatePhoto( i, 'scale', v ) }
							/>
							<RangeControl
								label={ __( 'Drift X (rem)', 'the-black-cap' ) }
								value={ photo.driftX }
								min={ -20 }
								max={ 20 }
								step={ 0.1 }
								onChange={ ( v ) => updatePhoto( i, 'driftX', v ) }
							/>
							<RangeControl
								label={ __( 'Drift Y (rem)', 'the-black-cap' ) }
								value={ photo.driftY }
								min={ -20 }
								max={ 20 }
								step={ 0.1 }
								onChange={ ( v ) => updatePhoto( i, 'driftY', v ) }
							/>
							<Button
								variant="link"
								isDestructive
								onClick={ () => removePhoto( i ) }
							>
								{ __( 'Remove photo', 'the-black-cap' ) }
							</Button>
						</div>
					) ) }
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => {
								const incoming = ( Array.isArray( media ) ? media : [ media ] ).map( ( m ) => ( {
									id:     m.id,
									url:    m.url,
									scale:  1,
									driftX: 0,
									driftY: 0,
								} ) );
								setAttributes( { photos: [ ...photos, ...incoming ] } );
							} }
							allowedTypes={ [ 'image' ] }
							multiple
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ __( 'Add photos', 'the-black-cap' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<RichText
					tagName="h2"
					className="legendaryTitle"
					value={ title }
					onChange={ ( v ) => setAttributes( { title: v } ) }
					placeholder={ __( 'Section title…', 'the-black-cap' ) }
				/>
				<RichText
					tagName="p"
					className="legendaryCopy"
					value={ copy }
					onChange={ ( v ) => setAttributes( { copy: v } ) }
					placeholder={ __( 'Story text…', 'the-black-cap' ) }
					multiline={ false }
				/>
				{ photos.length > 0 && (
					<div style={ { display: 'flex', flexWrap: 'wrap', gap: '1rem', marginTop: '1.5rem' } }>
						{ photos.map( ( p, i ) => (
							<img
								key={ i }
								src={ p.url }
								style={ { height: '100px', width: 'auto', borderRadius: '4px', objectFit: 'cover' } }
								alt=""
							/>
						) ) }
					</div>
				) }
			</section>
		</>
	);
}
