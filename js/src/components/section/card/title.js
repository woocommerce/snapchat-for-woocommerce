/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Subsection from '~/components/subsection';
import './title.scss';

const Title = ( props ) => {
	const { className, ...rest } = props;

	return (
		<Subsection.Title
			className={ classnames( 'sfw-section-card-title', className ) }
			{ ...rest }
		/>
	);
};

export default Title;
