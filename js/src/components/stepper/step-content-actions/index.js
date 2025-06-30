/**
 * Internal dependencies
 */
import './index.scss';

const StepContentActions = ( props ) => {
	const { className = '', ...rest } = props;

	return (
		<div
			className={ `sfw-step-content-actions ${ className }` }
			{ ...rest }
		/>
	);
};

export default StepContentActions;
