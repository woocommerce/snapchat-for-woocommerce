/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './subtitle.scss';

const Subtitle = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div
			className={ classnames( 'sfw-subsection-subtitle', className ) }
			{ ...rest }
		/>
	);
};

export default Subtitle;
