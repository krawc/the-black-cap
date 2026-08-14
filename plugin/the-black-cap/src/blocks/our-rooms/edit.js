import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, SelectControl, ToggleControl, TextControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

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
	const { heading, frames } = attributes;
	const blockProps = useBlockProps();

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
				<PanelBody title={ __( 'Section Heading', 'the-black-cap' ) } initialOpen>
					<TextControl
						label={ __( 'Heading text', 'the-black-cap' ) }
						value={ heading }
						onChange={ ( v ) => setAttributes( { heading: v } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Frames', 'the-black-cap' ) } initialOpen={ false }>
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

			<div { ...blockProps }>
				<Notice status="info" isDismissible={ false } className="tbc-editor-notice">
					{ __( 'Frame photos are clipped into shape by frontend JS, which only runs on the live site — frames below will appear empty here. Preview the page to see photos.', 'the-black-cap' ) }
				</Notice>
				<ServerSideRender
					block="the-black-cap/our-rooms"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
