/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { recordSfwEvent } from '~/utils/tracks';
import './index.scss';

/**
 * Renders a Button component with extra props.
 *
 * Set `loading` to `true` and it will render a disabled Button with a loading spinner indicator.
 *
 * Set `eventName` and upon `onClick` it will call `recordSfwEvent` with provided `eventName` and `eventProps`.
 *
 * Set `disabledInSandboxMode` to `true` and the button will be disabled while sandbox mode is
 * active, with `disableInSandboxModeLabel` displayed as a tooltip explaining why.
 *
 * ## Usage
 *
 * ```jsx
 * <AppButton loading>
 * 		Click Me
 * </AppButton>
 * ```
 *
 * @param {*} props Props to be forwarded to {@link Button}.
 * @param {boolean} [props.loading] If true, the button will be disabled and will display a loading spinner indicator beside the button text.
 * @param {boolean} [props.disabledInSandboxMode] If true, the button will be disabled while sandbox mode is active.
 * @param {string} [props.disableInSandboxModeLabel] Tooltip text explaining why the button is disabled in sandbox mode.
 */
const AppButton = ( props ) => {
	const {
		className,
		disabled,
		loading,
		eventName,
		eventProps,
		disabledInSandboxMode,
		disableInSandboxModeLabel,
		text: passedInText,
		onClick = () => {},
		...rest
	} = props;

	const handleClick = ( ...args ) => {
		if ( eventName ) {
			recordSfwEvent( eventName, eventProps );
		}

		onClick( ...args );
	};

	const isSandboxDisabled = Boolean(
		disabledInSandboxMode && sfwData.sandboxMode
	);
	const disabledButton = disabled || loading || isSandboxDisabled;
	const classes = [ 'sfw-app-button', className ];
	let text;

	if ( loading ) {
		text = <Spinner />;
	}

	if ( passedInText ) {
		text = (
			<>
				{ loading && <Spinner /> }
				{ passedInText }
			</>
		);

		if ( rest.icon ) {
			classes.push( 'sfw-app-button--icon-with-text' );
		}
		if ( rest.iconPosition === 'right' ) {
			classes.push( 'sfw-app-button--icon-position-right' );
		}
	}

	// A truly disabled `Button` never renders its tooltip, so keep the button focusable
	// and rely on `aria-disabled` to convey the state. `Button` then also stubs out the
	// click handlers on its own, so the button stays inert.
	const sandboxProps = isSandboxDisabled
		? {
				accessibleWhenDisabled: true,
				showTooltip: true,
				label: disableInSandboxModeLabel,
		  }
		: {};

	return (
		<Button
			className={ classnames( ...classes ) }
			disabled={ disabledButton }
			aria-disabled={ disabledButton }
			text={ text }
			onClick={ handleClick }
			{ ...rest }
			{ ...sandboxProps }
		/>
	);
};

export default AppButton;
