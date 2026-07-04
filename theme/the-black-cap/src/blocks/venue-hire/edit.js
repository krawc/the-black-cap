import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

const SPACE_LABELS = [
	__( 'Space 1 (bottom of plan)', 'the-black-cap' ),
	__( 'Space 2 (middle of plan)', 'the-black-cap' ),
	__( 'Space 3 (top of plan)',    'the-black-cap' ),
];

export default function Edit( { attributes, setAttributes } ) {
	const { slots } = attributes;

	const blockProps = useBlockProps( {
		style: { background: '#000', color: '#fff', padding: '2rem' },
	} );

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
				<PanelBody title={ __( 'Venue Mapping', 'the-black-cap' ) } initialOpen>
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
				<p style={ { margin: 0, opacity: 0.6, fontSize: '0.85rem' } }>
					{ __( 'Venue Hire — configure via block settings →', 'the-black-cap' ) }
				</p>
				<ul style={ { margin: '0.75rem 0 0', paddingLeft: '1.25rem', opacity: 0.85, fontSize: '0.8rem', lineHeight: 1.7 } }>
					{ slots.map( ( slot, idx ) => {
						const match = venues.find( ( v ) => v.id === slot.venueId );
						return (
							<li key={ idx }>
								{ SPACE_LABELS[ idx ] }{ ' → ' }
								{ match ? ( match.title?.rendered || `#${ match.id }` ) : __( '(none)', 'the-black-cap' ) }
							</li>
						);
					} ) }
				</ul>
			</div>
		</>
	);
}
