/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { recordSfwEvent } from '~/utils/tracks';
import './index.scss';

/**
 * Renders a Button component with extra props.
 *
 * Set `loading` to `true` and it will render a disabled Button with a loading spinner indicator.
 *
 * Set `eventName` and upon `onClick` it will call `recordSfwEvent` with provided `eventName` and `eventProps`.
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
 */
const AppButton = ( props ) => {
	const {
		className,
		disabled,
		loading,
		eventName,
		eventProps,
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

	const disabledButton = disabled || loading;
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

	return (
		<Button
			className={ classnames( ...classes ) }
			disabled={ disabledButton }
			aria-disabled={ disabledButton }
			text={ text }
			onClick={ handleClick }
			{ ...rest }
		/>
	);
};

export default AppButton;
