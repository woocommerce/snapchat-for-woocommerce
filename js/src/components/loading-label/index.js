/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a text with a leading <Spinner>.
 *
 * @param {Object} props React props.
 * @param {string} props.text Loading text.
 */
export default function LoadingLabel( { text } ) {
	return (
		<div className="sfw-loading-label">
			<Spinner />
			{ text }
		</div>
	);
}
