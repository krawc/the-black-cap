import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, Button, SelectControl, ToggleControl } from '@wordpress/components';

const SVG_OPTIONS = [
	{ label: 'Frame 1', value: 'Frame 1.svg' },
	{ label: 'Frame 2', value: 'Frame 2.svg' },
	{ label: 'Frame 3', value: 'Frame 3.svg' },
	{ label: 'Frame 4', value: 'Frame 4.svg' },
	{ label: 'Frame 5', value: 'Frame 5.svg' },
	{ label: 'Frame 6', value: 'Frame 6.svg' },
	{ label: 'Frame 7', value: 'Frame 7.svg' },
	{ label: 'Frame 8', value: 'Frame 8.svg' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { frames } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem', textAlign: 'center' } } );

	function updateFrame( idx, key, val ) {
		setAttributes( {
			frames: frames.map( ( fr, i ) => ( i === idx ? { ...fr, [ key ]: val } : fr ) ),
		} );
	}

	function removeFrame( idx ) {
		setAttributes( { frames: frames.filter( ( _, i ) => i !== idx ) } );
	}

	function addFrame() {
		setAttributes( { frames: [ ...frames, { svgFile: 'Frame 1.svg', photos: [], wide: false } ] } );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Frames', 'the-black-cap' ) } initialOpen>
					{ frames.map( ( frame, idx ) => (
						<div key={ idx } style={ { marginBottom: '1.5rem', paddingBottom: '1rem', borderBottom: '1px solid #e0e0e0' } }>
							<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' } }>
								<strong>{ __( 'Frame', 'the-black-cap' ) } { idx + 1 }</strong>
								<Button variant="link" isDestructive onClick={ () => removeFrame( idx ) }>
									{ __( 'Remove', 'the-black-cap' ) }
								</Button>
							</div>

							<SelectControl
								label={ __( 'Frame shape', 'the-black-cap' ) }
								value={ frame.svgFile }
								options={ SVG_OPTIONS }
								onChange={ ( v ) => updateFrame( idx, 'svgFile', v ) }
							/>

							<ToggleControl
								label={ __( 'Wide (span 2 grid columns)', 'the-black-cap' ) }
								checked={ frame.wide }
								onChange={ ( v ) => updateFrame( idx, 'wide', v ) }
							/>

							<MediaUploadCheck>
								<MediaUpload
									title={ __( 'Frame photos', 'the-black-cap' ) }
									onSelect={ ( media ) => {
										const urls = ( Array.isArray( media ) ? media : [ media ] ).map( ( m ) => m.url );
										updateFrame( idx, 'photos', urls );
									} }
									allowedTypes={ [ 'image' ] }
									multiple
									value={ frame.photos }
									render={ ( { open } ) => (
										<Button variant="secondary" onClick={ open }>
											{ frame.photos.length > 0
												? `${ __( 'Edit photos', 'the-black-cap' ) } (${ frame.photos.length })`
												: __( 'Add photos', 'the-black-cap' ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>

							{ frame.photos.length > 0 && (
								<div style={ { display: 'flex', flexWrap: 'wrap', gap: '4px', marginTop: '6px' } }>
									{ frame.photos.slice( 0, 4 ).map( ( url, pi ) => (
										<img
											key={ pi }
											src={ url }
											style={ { width: '36px', height: '36px', objectFit: 'cover', borderRadius: '2px' } }
											alt=""
										/>
									) ) }
									{ frame.photos.length > 4 && (
										<span style={ { fontSize: '0.7rem', color: '#888', alignSelf: 'center' } }>
											+{ frame.photos.length - 4 }
										</span>
									) }
								</div>
							) }
						</div>
					) ) }
					<Button variant="secondary" onClick={ addFrame }>
						{ __( '+ Add frame', 'the-black-cap' ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<h2 className="roomsHeadline">{ __( 'Our Rooms', 'the-black-cap' ) }</h2>
				<p style={ { color: 'rgba(255,255,255,0.4)', fontSize: '0.85rem', margin: '0.5rem 0 0' } }>
					{ frames.length === 0
						? __( 'Add frames in the sidebar →', 'the-black-cap' )
						: `${ frames.length } ${ __( 'frame(s) configured', 'the-black-cap' ) }` }
				</p>
			</section>
		</>
	);
}
