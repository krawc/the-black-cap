import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, Button, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

function ItemImagePicker( { imageId, onChange } ) {
	const mediaUrl = useSelect(
		( select ) => {
			if ( ! imageId ) return null;
			const media = select( 'core' ).getMedia( imageId );
			return media?.source_url ?? null;
		},
		[ imageId ]
	);

	return (
		<MediaUploadCheck>
			<MediaUpload
				onSelect={ ( media ) => onChange( media.id ) }
				allowedTypes={ [ 'image' ] }
				value={ imageId || 0 }
				render={ ( { open } ) => (
					<div style={ { display: 'flex', flexDirection: 'column', gap: '2px', alignItems: 'center' } }>
						<button
							type="button"
							onClick={ open }
							title={ __( 'Set photo', 'the-black-cap' ) }
							style={ {
								width: '2.4rem',
								height: '2.4rem',
								padding: 0,
								border: '1px dashed #aaa',
								borderRadius: '3px',
								background: 'transparent',
								cursor: 'pointer',
								overflow: 'hidden',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								fontSize: '1rem',
								color: '#aaa',
								flexShrink: 0,
							} }
						>
							{ mediaUrl
								? <img src={ mediaUrl } alt="" style={ { width: '100%', height: '100%', objectFit: 'cover' } } />
								: '📷' }
						</button>
						{ imageId ? (
							<button
								type="button"
								onClick={ () => onChange( 0 ) }
								style={ { background: 'none', border: 'none', cursor: 'pointer', fontSize: '0.6rem', color: '#cc0000', padding: 0, lineHeight: 1 } }
								aria-label={ __( 'Remove photo', 'the-black-cap' ) }
							>✕</button>
						) : null }
					</div>
				) }
			/>
		</MediaUploadCheck>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { heading, sections } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem 2rem 4rem' } } );

	function updateSection( si, key, val ) {
		setAttributes( {
			sections: sections.map( ( sec, i ) => ( i === si ? { ...sec, [ key ]: val } : sec ) ),
		} );
	}

	function removeSection( si ) {
		setAttributes( { sections: sections.filter( ( _, i ) => i !== si ) } );
	}

	function addSection() {
		setAttributes( { sections: [ ...sections, { category: '', items: [] } ] } );
	}

	function updateItem( si, ii, key, val ) {
		setAttributes( {
			sections: sections.map( ( sec, i ) =>
				i !== si
					? sec
					: {
							...sec,
							items: sec.items.map( ( item, j ) =>
								j === ii ? { ...item, [ key ]: val } : item
							),
					  }
			),
		} );
	}

	function removeItem( si, ii ) {
		setAttributes( {
			sections: sections.map( ( sec, i ) =>
				i !== si ? sec : { ...sec, items: sec.items.filter( ( _, j ) => j !== ii ) }
			),
		} );
	}

	function addItem( si ) {
		setAttributes( {
			sections: sections.map( ( sec, i ) =>
				i !== si ? sec : { ...sec, items: [ ...sec.items, { name: '', price: '', imageId: 0 } ] }
			),
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
				<PanelBody title={ __( 'Menu Sections', 'the-black-cap' ) } initialOpen={ false }>
					{ sections.map( ( sec, si ) => (
						<div key={ si } style={ { marginBottom: '1.5rem', paddingBottom: '1rem', borderBottom: '1px solid #e0e0e0' } }>
							<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' } }>
								<strong>{ sec.category || `${ __( 'Category', 'the-black-cap' ) } ${ si + 1 }` }</strong>
								<Button variant="link" isDestructive onClick={ () => removeSection( si ) }>
									{ __( 'Remove', 'the-black-cap' ) }
								</Button>
							</div>
							<TextControl
								label={ __( 'Category name', 'the-black-cap' ) }
								value={ sec.category }
								onChange={ ( v ) => updateSection( si, 'category', v ) }
							/>
							{ sec.items.map( ( item, ii ) => (
								<div key={ ii } style={ { display: 'grid', gridTemplateColumns: '2.4rem 1fr 1fr auto', gap: '0.4rem', alignItems: 'end', marginBottom: '0.4rem' } }>
									<div style={ { paddingBottom: '4px' } }>
										{ ii === 0 && <div style={ { fontSize: '0.65rem', fontWeight: 600, marginBottom: '4px', color: '#444' } }>{ __( 'Photo', 'the-black-cap' ) }</div> }
										<ItemImagePicker
											imageId={ item.imageId || 0 }
											onChange={ ( id ) => updateItem( si, ii, 'imageId', id ) }
										/>
									</div>
									<TextControl
										label={ ii === 0 ? __( 'Item', 'the-black-cap' ) : undefined }
										hideLabelFromVision={ ii !== 0 }
										value={ item.name }
										placeholder={ __( 'Item name', 'the-black-cap' ) }
										onChange={ ( v ) => updateItem( si, ii, 'name', v ) }
									/>
									<TextControl
										label={ ii === 0 ? __( 'Price', 'the-black-cap' ) : undefined }
										hideLabelFromVision={ ii !== 0 }
										value={ item.price }
										placeholder={ __( '£0.00', 'the-black-cap' ) }
										onChange={ ( v ) => updateItem( si, ii, 'price', v ) }
									/>
									<Button
										variant="link"
										isDestructive
										style={ { marginBottom: '8px' } }
										onClick={ () => removeItem( si, ii ) }
										aria-label={ __( 'Remove item', 'the-black-cap' ) }
									>
										✕
									</Button>
								</div>
							) ) }
							<Button variant="link" onClick={ () => addItem( si ) }>
								{ __( '+ Add item', 'the-black-cap' ) }
							</Button>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addSection }>
						{ __( '+ Add section', 'the-black-cap' ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<h2 className="menuHeadline">{ heading }</h2>
				{ sections.length === 0 ? (
					<p style={ { color: 'rgba(255,255,255,0.4)', fontSize: '0.85rem' } }>
						{ __( 'Add sections in the sidebar →', 'the-black-cap' ) }
					</p>
				) : (
					<div className="menuList">
						{ sections.map( ( sec, si ) => (
							<div key={ si } className="menuCategory">
								<p className="menuCategoryName">{ sec.category }</p>
								{ sec.items.map( ( item, ii ) => (
									<div key={ ii } className="menuItem">
										<span className="menuItemName">{ item.name }</span>
										<span className="menuItemPrice">{ item.price }</span>
									</div>
								) ) }
							</div>
						) ) }
					</div>
				) }
			</section>
		</>
	);
}
