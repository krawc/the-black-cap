import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

const SPACE_LABELS = [
	__( 'Space 1 (bottom of plan)', 'the-black-cap' ),
	__( 'Space 2 (middle of plan)', 'the-black-cap' ),
	__( 'Space 3 (top of plan)',    'the-black-cap' ),
];

export default function Edit( { attributes, setAttributes } ) {
	const { heading, slots } = attributes;
	const blockProps = useBlockProps();

	const venues = useSelect( ( select ) => {
		return select( coreStore ).getEntityRecords( 'postType', 'tbc_venue', {
			per_page: -1,
			status:   'publish',
			_fields:  'id,title',
		} ) || [];
	}, [] );

	const venueOptions = [
		{ label: '— No venue —', value: 0 },
		...venues.map( ( v ) => ( {
			label: v.title?.rendered || `Venue ${ v.id }`,
			value: v.id,
		} ) ),
	];

	function updateSlot( idx, venueId ) {
		setAttributes( {
			slots: slots.map( ( s, i ) => ( i === idx ? { venueId } : s ) ),
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
				<PanelBody title={ __( 'Venue Mapping', 'the-black-cap' ) } initialOpen={ false }>
					{ slots.map( ( slot, idx ) => (
						<SelectControl
							key={ idx }
							label={ SPACE_LABELS[ idx ] }
							value={ slot.venueId }
							options={ venueOptions }
							onChange={ ( v ) => updateSlot( idx, parseInt( v, 10 ) || 0 ) }
						/>
					) ) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Notice status="info" isDismissible={ false } className="tbc-editor-notice">
					{ __( 'Hover/click switching between spaces is frontend JS and won\'t work here — the floor plan and the first assigned space\'s details are shown below. Preview the page for the interactive version.', 'the-black-cap' ) }
				</Notice>
				<ServerSideRender
					block="the-black-cap/venue-hire"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
