import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { menuSlug, address } = attributes;
	const blockProps = useBlockProps();

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

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/footer"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
