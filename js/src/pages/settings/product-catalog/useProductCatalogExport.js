/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { EXPORT_CSV_ACTION } from './constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * @typedef {Object} ProductCatalogExport
 * @property {Function} generateCsv Function to initiate the CSV export process.
 */

const { exportNonce } = sfwData;

/**
 * Custom React hook to handle the export of a product catalog as a CSV file.
 * Integrates with WordPress Heartbeat API to poll export status and manages export state.
 *
 * @param {Function} onGenerateCsvSuccess Callback function to execute when CSV generation is successful.
 * @param {Function} onGenerateCsvError Callback function to execute when CSV generation fails.
 * @return {ProductCatalogExport} The current export status and functions to manage the export process.
 */
const useProductCatalogExport = (
	onGenerateCsvSuccess,
	onGenerateCsvError
) => {
	const { createNotice } = useDispatchCoreNotices();

	const generateCsv = useCallback( async () => {
		try {
			const res = await window.jQuery.post( window.ajaxurl, {
				action: EXPORT_CSV_ACTION,
				security: exportNonce,
			} );

			if ( res.success ) {
				onGenerateCsvSuccess();
				return;
			}

			if (
				! res.success &&
				res.data?.code === 'snapchat_for_woocommerce_no_products_found'
			) {
				createNotice(
					'error',
					__(
						'No products found. Please create products to generate the CSV.',
						'snapchat-for-woocommerce'
					)
				);
				return;
			}

			createNotice(
				'error',
				__( 'An error occurred', 'snapchat-for-woocommerce' )
			);
			onGenerateCsvError();
		} catch ( error ) {
			createNotice(
				'error',
				sprintf(
					// translators: %s: The error message returned from the CSV generation process.
					__(
						'CSV generation failed: %s',
						'snapchat-for-woocommerce'
					),
					error.message
				)
			);
			onGenerateCsvError();
		}
	}, [ createNotice, onGenerateCsvSuccess, onGenerateCsvError ] );

	return {
		generateCsv,
	};
};

export default useProductCatalogExport;
