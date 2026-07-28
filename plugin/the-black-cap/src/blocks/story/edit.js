import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { title, copy } = attributes;
	const blockProps = useBlockProps( { style: { background: '#000', color: '#fff', padding: '2rem 2rem 4rem' } } );

	return (
		<section { ...blockProps }>
			<RichText
				tagName="h2"
				className="legendaryTitle"
				value={ title }
				onChange={ ( v ) => setAttributes( { title: v } ) }
				placeholder={ __( 'Section title…', 'the-black-cap' ) }
			/>
			<RichText
				tagName="p"
				className="legendaryCopy"
				value={ copy }
				onChange={ ( v ) => setAttributes( { copy: v } ) }
				placeholder={ __( 'Story text…', 'the-black-cap' ) }
				multiline={ false }
			/>
		</section>
	);
}
