import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

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
	const blockProps = useBlockProps( {
		style: { background: '#000', color: '#fff', padding: '2rem', textAlign: 'center' },
	} );

	const rooms = useSelect( ( select ) => {
		return select( coreStore ).getEntityRecords( 'postType', 'tbc_room', {
			per_page: -1,
			status:   'publish',
			_fields:  'id,title,meta',
		} ) || [];
	}, [] );

	const roomOptions = [
		{ label: '— No room —', value: 0 },
		...rooms.map( ( r ) => ( {
			label: r.title?.rendered || `Room ${ r.id }`,
			value: r.id,
		} ) ),
	];

	function updateFrame( idx, patch ) {
		setAttributes( {
			frames: frames.map( ( fr, i ) => ( i === idx ? { ...fr, ...patch } : fr ) ),
		} );
	}

	function removeFrame( idx ) {
		setAttributes( { frames: frames.filter( ( _, i ) => i !== idx ) } );
	}

	function addFrame() {
		setAttributes( {
			frames: [ ...frames, { svgFile: 'Frame 1.svg', roomId: 0, wide: false } ],
		} );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Frames', 'the-black-cap' ) } initialOpen>
					{ frames.map( ( frame, idx ) => {
						const room     = rooms.find( ( r ) => r.id === frame.roomId );
						const imgCount = room?.meta?.tbc_room_image_ids?.length ?? 0;
						return (
							<div
								key={ idx }
								style={ {
									marginBottom:  '1.5rem',
									paddingBottom: '1rem',
									borderBottom:  '1px solid #e0e0e0',
								} }
							>
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
									onChange={ ( v ) => updateFrame( idx, { svgFile: v } ) }
								/>

								<SelectControl
									label={ __( 'Room', 'the-black-cap' ) }
									value={ frame.roomId || 0 }
									options={ roomOptions }
									onChange={ ( v ) => updateFrame( idx, { roomId: Number( v ) } ) }
								/>

								{ room && (
									<p style={ { fontSize: '0.8rem', color: '#666', margin: '0 0 0.75rem' } }>
										{ imgCount } image{ imgCount !== 1 ? 's' : '' } in this room
									</p>
								) }

								<ToggleControl
									label={ __( 'Wide (span 2 columns)', 'the-black-cap' ) }
									checked={ !! frame.wide }
									onChange={ ( v ) => updateFrame( idx, { wide: v } ) }
								/>
							</div>
						);
					} ) }
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
