import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { sections } = attributes;
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
				i !== si ? sec : { ...sec, items: [ ...sec.items, { name: '', price: '' } ] }
			),
		} );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Menu Sections', 'the-black-cap' ) } initialOpen>
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
								<div key={ ii } style={ { display: 'grid', gridTemplateColumns: '1fr 1fr auto', gap: '0.4rem', alignItems: 'end', marginBottom: '0.25rem' } }>
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
				<h2 className="menuHeadline">{ __( 'The Menu', 'the-black-cap' ) }</h2>
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
