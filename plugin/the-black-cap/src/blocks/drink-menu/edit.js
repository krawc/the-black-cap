import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, tabs } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem 2rem 4rem' } } );

	const totalSections = ( tabs ?? [] ).reduce( ( sum, t ) => sum + ( t.sections?.length ?? 0 ), 0 );
	const totalItems    = ( tabs ?? [] ).reduce( ( sum, t ) =>
		sum + ( t.sections ?? [] ).reduce( ( s2, sec ) => s2 + ( sec.items?.length ?? 0 ), 0 ), 0 );

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
			</InspectorControls>

			<section { ...blockProps }>
				<h2 className="menuHeadline">{ heading }</h2>
				{ tabs && tabs.length > 0 ? (
					<div style={ { marginTop: '1rem' } }>
						<div style={ { display: 'flex', gap: '0.5rem', marginBottom: '1rem' } }>
							{ tabs.map( ( tab ) => (
								<span key={ tab.id } style={ {
									padding: '0.3rem 1rem',
									border: '1px solid rgba(255,255,255,0.3)',
									borderRadius: '999px',
									fontSize: '0.75rem',
									fontWeight: 700,
									letterSpacing: '0.08em',
									textTransform: 'uppercase',
									color: 'rgba(255,255,255,0.7)',
								} }>{ tab.label }</span>
							) ) }
						</div>
						<p style={ { color: 'rgba(255,255,255,0.4)', fontSize: '0.8rem', margin: 0 } }>
							{ tabs.length } { __( 'tabs', 'the-black-cap' ) } &middot; { totalSections } { __( 'sections', 'the-black-cap' ) } &middot; { totalItems } { __( 'items', 'the-black-cap' ) }
						</p>
						<p style={ { color: 'rgba(255,255,255,0.25)', fontSize: '0.72rem', marginTop: '0.5rem' } }>
							{ __( 'Run the Setup Wizard to reload full menu data.', 'the-black-cap' ) }
						</p>
					</div>
				) : (
					<p style={ { color: 'rgba(255,255,255,0.4)', fontSize: '0.85rem' } }>
						{ __( 'Run the Setup Wizard to populate the menu.', 'the-black-cap' ) }
					</p>
				) }
			</section>
		</>
	);
}
