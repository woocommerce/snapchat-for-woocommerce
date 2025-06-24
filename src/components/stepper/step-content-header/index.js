/**
 * Internal dependencies
 */
import './index.scss';

const StepContentHeader = ( props ) => {
	const { className = '', title, description } = props;

	return (
		<header className={ `snap4woostep-content-header ${ className }` }>
			<h1>{ title }</h1>
			<div className="snap4woostep-content-header__description">
				{ description }
			</div>
		</header>
	);
};

export default StepContentHeader;
