/**
 * External dependencies
 */
import { CardBody } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './body.scss';

const Body = ( props ) => {
	const { className, ...rest } = props;

	return (
		<CardBody
			className={ classnames( 'sfw-section-card-body', className ) }
			{ ...rest }
		></CardBody>
	);
};

export default Body;
