/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './helper-text.scss';

const HelperText = ( props ) => {
	const { className, children } = props;

	return (
		<div
			className={ classnames( 'sfw-subsection-helper-text', className ) }
		>
			{ children }
		</div>
	);
};

export default HelperText;
