import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { menuSlug, address, phone, email, subscribeFormId } = attributes;
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
				<PanelBody title={ __( 'Subscribe Form', 'the-black-cap' ) }>
					<TextControl
						label={ __( 'FluentForms ID', 'the-black-cap' ) }
						help={ __( 'Set to 0 to hide the Subscribe button.', 'the-black-cap' ) }
						value={ String( subscribeFormId ) }
						onChange={ ( v ) => setAttributes( { subscribeFormId: parseInt( v, 10 ) || 0 } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="the-black-cap/hero-nav"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
