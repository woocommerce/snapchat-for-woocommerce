/**
 * External dependencies
 */
import { Tooltip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import './index.scss';

/**
 * Makes a sandbox-disabled control hoverable and keyboard-focusable so its
 * explanatory tooltip remains available.
 *
 * @param {Object}          props          Component properties.
 * @param {JSX.Element}     props.children Disabled control.
 * @param {string}          props.message  Tooltip message.
 * @return {JSX.Element} The control, optionally wrapped in a tooltip target.
 */
const SandboxDisabledControl = ( { children, message } ) => {
	if ( ! sfwData.sandboxMode ) {
		return children;
	}

	return (
		<Tooltip text={ message }>
			<span className="sfw-sandbox-disabled-control" tabIndex="0">
				{ children }
			</span>
		</Tooltip>
	);
};

export default SandboxDisabledControl;
