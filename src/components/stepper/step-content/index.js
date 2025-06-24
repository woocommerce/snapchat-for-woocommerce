/**
 * Internal dependencies
 */
import './index.scss';

const StepContent = ( props ) => {
	const { className = '', children, ...rest } = props;

	return (
		<div className={ `snap4woostep-content ${ className }` } { ...rest }>
			<div className="snap4woostep-content__container">{ children }</div>
		</div>
	);
};

export default StepContent;
