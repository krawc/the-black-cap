import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit( { attributes, setAttributes } ) {
	const { menuSlug, address } = attributes;
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
				<PanelBody title={ __( 'Footer Links Menu', 'the-black-cap' ) } initialOpen>
					<SelectControl
						label={ __( 'Menu', 'the-black-cap' ) }
						help={ __( 'Falls back to Privacy Policy / Cookie Policy / Terms & Conditions / Accessibility links if no menu is selected or found.', 'the-black-cap' ) }
						value={ menuSlug }
						options={ menuOptions }
						onChange={ ( v ) => setAttributes( { menuSlug: v } ) }
					/>
					{ ! menuSlug && (
						<Notice status="warning" isDismissible={ false }>
							{ __( 'No menu selected — fallback links will be shown on the frontend.', 'the-black-cap' ) }
						</Notice>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Byline', 'the-black-cap' ) }>
					<TextControl
						label={ __( 'Address', 'the-black-cap' ) }
						value={ address }
						onChange={ ( v ) => setAttributes( { address: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<footer { ...blockProps } style={ { ...blockProps.style, padding: '2rem', textAlign: 'center' } }>
				<p style={ { fontSize: '0.72rem', textTransform: 'uppercase', letterSpacing: '0.12em', color: 'rgba(255,255,255,0.45)', margin: '0 0 0.5rem' } }>
					{ __( 'Site Footer Block', 'the-black-cap' ) }
				</p>
				<p style={ { fontSize: '0.85rem', color: 'rgba(255,255,255,0.6)', margin: 0 } }>
					{ __( 'Menu:', 'the-black-cap' ) } <strong>{ menuSlug || __( 'None (fallback links)', 'the-black-cap' ) }</strong>
					&nbsp;·&nbsp;{ address }
				</p>
			</footer>
		</>
	);
}
