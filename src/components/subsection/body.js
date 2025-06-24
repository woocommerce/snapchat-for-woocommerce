/**
 * Internal dependencies
 */
import './body.scss';

const Body = ( props ) => {
	const { children } = props;

	return <div className="snap4woo-subsection-body">{ children }</div>;
};

export default Body;
