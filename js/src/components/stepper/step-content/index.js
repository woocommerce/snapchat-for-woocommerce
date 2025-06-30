/**
 * Internal dependencies
 */
import './index.scss';

const StepContent = ( props ) => {
	const { className = '', children, ...rest } = props;

	return (
		<div className={ `sfw-step-content ${ className }` } { ...rest }>
			<div className="sfw-step-content__container">{ children }</div>
		</div>
	);
};

export default StepContent;
