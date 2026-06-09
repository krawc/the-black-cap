import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit( { attributes, setAttributes } ) {
	const { menuSlug, address, phone, email } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff' } } );

	const menus = useSelect( ( select ) => {
		return select( coreStore ).getEntityRecords( 'taxonomy', 'nav_menu', { per_page: -1 } ) || [];
	}, [] );

	const menuOptions = [
		{ label: __( '— Select a menu —', 'the-black-cap' ), value: '' },
		...( menus || [] ).map( ( m ) => ( { label: m.name, value: m.slug } ) ),
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Navigation Menu', 'the-black-cap' ) } initialOpen>
					<SelectControl
						label={ __( 'Menu', 'the-black-cap' ) }
						help={ __( 'The selected menu drives the 5 orbital nav buttons. Up to 5 items are shown; assign section anchors (e.g. #story) or full URLs as each item\'s URL in Appearance → Menus.', 'the-black-cap' ) }
						value={ menuSlug }
						options={ menuOptions }
						onChange={ ( v ) => setAttributes( { menuSlug: v } ) }
					/>
					{ ! menuSlug && (
						<Notice status="warning" isDismissible={ false }>
							{ __( 'No menu selected — nav buttons will be hidden on the frontend.', 'the-black-cap' ) }
						</Notice>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Venue Contact Info', 'the-black-cap' ) }>
					<TextControl
						label={ __( 'Address', 'the-black-cap' ) }
						value={ address }
						onChange={ ( v ) => setAttributes( { address: v } ) }
					/>
					<TextControl
						label={ __( 'Phone', 'the-black-cap' ) }
						value={ phone }
						onChange={ ( v ) => setAttributes( { phone: v } ) }
					/>
					<TextControl
						label={ __( 'Email', 'the-black-cap' ) }
						value={ email }
						onChange={ ( v ) => setAttributes( { email: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div style={ { textAlign: 'center', padding: '3rem 2rem', opacity: 0.85 } }>
					<p style={ { fontSize: '0.72rem', textTransform: 'uppercase', letterSpacing: '0.12em', color: 'rgba(255,255,255,0.45)', margin: '0 0 0.75rem' } }>
						{ __( 'Hero / Navigation Block', 'the-black-cap' ) }
					</p>
					<p style={ { fontFamily: '"Train One", sans-serif', fontSize: '2.5rem', textTransform: 'uppercase', margin: '0 0 0.5rem', textShadow: '0 0 1rem rgba(214,92,255,.55)' } }>
						The Black Cap
					</p>
					<p style={ { fontSize: '0.85rem', color: 'rgba(255,255,255,0.5)', margin: 0 } }>
						{ __( 'Menu:', 'the-black-cap' ) } <strong>{ menuSlug || __( 'None', 'the-black-cap' ) }</strong>
						&nbsp;·&nbsp;{ address }
					</p>
				</div>
			</section>
		</>
	);
}
