import { __ } from '@wordpress/i18n';
import { useBlockProps, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, TextControl, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

function uid() {
	return Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 6 );
}

function TimestampEntry( { ts, idx, total, onChange, onRemove, onMoveUp, onMoveDown } ) {
	const images = useSelect(
		( select ) =>
			( ts.imageIds || [] ).map( ( id ) => {
				const m = select( coreStore ).getMedia( id );
				return {
					id,
					thumb:
						m?.media_details?.sizes?.thumbnail?.source_url ||
						m?.source_url ||
						null,
					alt: m?.alt_text || '',
				};
			} ),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ JSON.stringify( ts.imageIds ) ]
	);

	return (
		<div className="tl-editor__entry">
			<div className="tl-editor__entry-header">
				<span className="tl-editor__entry-num">
					{ __( 'Entry', 'the-black-cap' ) } { idx + 1 }
				</span>
				<div className="tl-editor__entry-btns">
					<Button
						size="small"
						variant="tertiary"
						icon="arrow-up-alt2"
						disabled={ idx === 0 }
						onClick={ onMoveUp }
						label={ __( 'Move up', 'the-black-cap' ) }
					/>
					<Button
						size="small"
						variant="tertiary"
						icon="arrow-down-alt2"
						disabled={ idx === total - 1 }
						onClick={ onMoveDown }
						label={ __( 'Move down', 'the-black-cap' ) }
					/>
					<Button
						size="small"
						variant="tertiary"
						isDestructive
						icon="trash"
						onClick={ onRemove }
						label={ __( 'Remove', 'the-black-cap' ) }
					/>
				</div>
			</div>

			<TextControl
				label={ __( 'Years', 'the-black-cap' ) }
				value={ ts.years || '' }
				onChange={ ( val ) => onChange( { years: val } ) }
				placeholder={ __( 'e.g. 1960s–1980s', 'the-black-cap' ) }
			/>

			<TextControl
				label={ __( 'Title', 'the-black-cap' ) }
				value={ ts.title || '' }
				onChange={ ( val ) => onChange( { title: val } ) }
				placeholder={ __( 'e.g. The Early Years', 'the-black-cap' ) }
			/>

			<TextareaControl
				label={ __( 'Description', 'the-black-cap' ) }
				value={ ts.description || '' }
				onChange={ ( val ) => onChange( { description: val } ) }
				rows={ 3 }
			/>

			{ images.length > 0 && (
				<div className="tl-editor__thumbs">
					{ images.map( ( img ) =>
						img.thumb ? (
							<img
								key={ img.id }
								src={ img.thumb }
								alt={ img.alt }
								className="tl-editor__thumb"
							/>
						) : (
							<div key={ img.id } className="tl-editor__thumb tl-editor__thumb--missing" />
						)
					) }
				</div>
			) }

			<MediaUploadCheck>
				<MediaUpload
					onSelect={ ( media ) => {
						const ids = Array.isArray( media )
							? media.map( ( m ) => m.id )
							: [ media.id ];
						onChange( { imageIds: ids } );
					} }
					allowedTypes={ [ 'image' ] }
					multiple
					gallery
					value={ ts.imageIds || [] }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ ( ts.imageIds || [] ).length
								? __( 'Edit photos', 'the-black-cap' )
								: __( 'Add photos', 'the-black-cap' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { introText, timestamps } = attributes;
	const blockProps = useBlockProps( { className: 'tl-editor' } );

	function addEntry() {
		setAttributes( {
			timestamps: [
				...timestamps,
				{ id: uid(), title: '', description: '', imageIds: [] },
			],
		} );
	}

	function updateEntry( idx, patch ) {
		setAttributes( {
			timestamps: timestamps.map( ( t, i ) =>
				i === idx ? { ...t, ...patch } : t
			),
		} );
	}

	function removeEntry( idx ) {
		setAttributes( { timestamps: timestamps.filter( ( _, i ) => i !== idx ) } );
	}

	function moveEntry( idx, dir ) {
		const arr = [ ...timestamps ];
		const to = idx + dir;
		if ( to < 0 || to >= arr.length ) return;
		[ arr[ idx ], arr[ to ] ] = [ arr[ to ], arr[ idx ] ];
		setAttributes( { timestamps: arr } );
	}

	return (
		<div { ...blockProps }>
			<div className="tl-editor__intro-wrap">
				<TextareaControl
					label={ __( 'Intro Text', 'the-black-cap' ) }
					value={ introText }
					onChange={ ( val ) => setAttributes( { introText: val } ) }
					placeholder={ __( 'Timeline intro paragraph…', 'the-black-cap' ) }
					rows={ 3 }
				/>
			</div>

			{ timestamps.length > 0 && (
				<div className="tl-editor__entries">
					{ timestamps.map( ( ts, idx ) => (
						<TimestampEntry
							key={ ts.id }
							ts={ ts }
							idx={ idx }
							total={ timestamps.length }
							onChange={ ( patch ) => updateEntry( idx, patch ) }
							onRemove={ () => removeEntry( idx ) }
							onMoveUp={ () => moveEntry( idx, -1 ) }
							onMoveDown={ () => moveEntry( idx, 1 ) }
						/>
					) ) }
				</div>
			) }

			<div className="tl-editor__add-wrap">
				<Button variant="primary" onClick={ addEntry }>
					{ __( '+ Add Entry', 'the-black-cap' ) }
				</Button>
			</div>
		</div>
	);
}
