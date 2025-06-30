/**
 * External dependencies
 */
import { CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './footer.scss';

const Footer = ( props ) => {
	const { children, ...restProps } = props;

	return (
		<CardFooter className="sfw-section-card-footer" { ...restProps }>
			{ children }
		</CardFooter>
	);
};

export default Footer;
