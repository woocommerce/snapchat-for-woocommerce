/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import AppButton from '~/components/app-button';
import AccountCard from '~/components/account-card';
import useSettings from '~/hooks/useSettings';
import useExportPoller from './useExportPoller';
import useProductCatalogExport from './useProductCatalogExport';
import './index.scss';

/**
 * When the `Generate CSV` button is clicked.
 *
 * @event sfw_generate_csv_button_click
 */

/**
 * When the `Regenerate CSV` button is clicked.
 *
 * @event sfw_regenerate_csv_button_click
 */

/**
 * ProductCatalog component for managing and exporting the product catalog as a CSV file.
 *
 * This component allows users to:
 * - Regenerate the product catalog CSV file.
 * - Download the latest exported CSV file.
 * - View the last export timestamp.
 * - See contextual help and documentation links.
 *
 * State management includes tracking export progress, file URL, last export time, and heartbeat connection.
 *
 * @fires sfw_generate_csv_button_click
 * @fires sfw_regenerate_csv_button_click
 *
 * @return {JSX.Element} The rendered ProductCatalog settings UI.
 */
const ProductCatalog = () => {
	const {
		shouldTriggerExport,
		lastExportTimeStamp,
		exportFileUrl,
		hasFinishedResolution,
	} = useSettings();
	// Whether we want to connect the heartbeat immediately as soon as the Heartbeat component mounts.
	const [ exportInProgress, setExportInProgress ] = useState(
		sfwData.isExportInProgress === '1'
	);
	const [ fileUrl, setFileUrl ] = useState( sfwData.exportFileUrl || null );
	const [ lastExported, setLastExported ] = useState(
		sfwData.lastTimestamp || null
	);
	const hasExport = fileUrl && lastExported;

	// Trigger a heartbeat connection as soon as we get a successfull response from the server
	// when the user clicks on the "Regenerate CSV" button.
	const onGenerateCsvSuccess = () => {
		setExportInProgress( true );
	};

	// If the CSV generation fails, we reset the state to ensure the UI reflects that no export is in progress.
	// This prevents the UI from showing a download link or last exported timestamp when there is no valid export.
	// It also stops the heartbeat connection to avoid unnecessary requests.
	const onGenerateCsvError = () => {
		setExportInProgress( false );
		setFileUrl( null );
		setLastExported( null );
	};

	const { generateCsv } = useProductCatalogExport(
		onGenerateCsvSuccess,
		onGenerateCsvError
	);

	const handleOnGenerateCsvClick = () => {
		generateCsv();
	};

	const handleOnTick = useCallback( ( response ) => {
		const { status } = response;

		switch ( status ) {
			case 'idle':
				setExportInProgress( false );
				break;
			case 'completed':
				setExportInProgress( false );
				setFileUrl( response.fileUrl );
				setLastExported( response.lastExport );
				break;
			case 'in-progress':
				setExportInProgress( true );
				break;

			default:
				break;
		}
	}, [] );

	const getDescription = () => {
		if ( exportInProgress ) {
			return __(
				'We’re generating your CSV file… This may take a few seconds.',
				'snapchat-for-woocommerce'
			);
		}

		if ( ! lastExported ) {
			return __(
				'Your product catalog is not synced to Snapchat yet. Generate a CSV to manually upload.',
				'snapchat-for-woocommerce'
			);
		}

		return sprintf(
			// translators: %s: The date and time when the product catalog was last exported.
			__( 'Last exported on %s.', 'snapchat-for-woocommerce' ),
			lastExported
		);
	};

	const getIndicator = () => {
		if ( hasExport ) {
			return (
				<Flex spacing={ 4 } wrap="wrap">
					<AppButton
						variant="secondary"
						onClick={ handleOnGenerateCsvClick }
						loading={ exportInProgress }
						eventName="sfw_regenerate_csv_button_click"
						eventProps={ {
							context: 'settings',
						} }
					>
						{ __( 'Regenerate CSV', 'snapchat-for-woocommerce' ) }
					</AppButton>
				</Flex>
			);
		}

		return (
			<AppButton
				variant="secondary"
				onClick={ handleOnGenerateCsvClick }
				loading={ exportInProgress }
				eventName="sfw_generate_csv_button_click"
				eventProps={ {
					context: 'settings',
				} }
			>
				{ __( 'Generate CSV', 'snapchat-for-woocommerce' ) }
			</AppButton>
		);
	};

	useExportPoller( exportInProgress, handleOnTick );

	useEffect( () => {
		if ( ! exportInProgress ) {
			return;
		}

		setFileUrl( null );
		setLastExported( null );
	}, [ exportInProgress ] );

	useEffect( () => {
		/**
		 * Trigger catalog CSV generation as soon as the
		 * merchant has successfully onboarded.
		 */
		if ( shouldTriggerExport && hasFinishedResolution ) {
			generateCsv();
		}
	}, [ shouldTriggerExport, hasFinishedResolution ] );

	useEffect( () => {
		if ( lastExportTimeStamp ) {
			setLastExported( lastExportTimeStamp );
		}

		if ( exportFileUrl ) {
			setFileUrl( exportFileUrl );
		}
	}, [ lastExportTimeStamp, exportFileUrl ] );

	return (
		<>
			<AccountCard
				className="sfw-product-catalog"
				title={ __(
					'Export Product Catalog',
					'snapchat-for-woocommerce'
				) }
				description={ getDescription() }
				indicator={ getIndicator() }
			>
				{ lastExported && ! fileUrl && (
					<div className="sfw-product-catalog__help">
						<p>
							{ __(
								'The CSV file may have been deleted and could not be found. Click "Generate CSV" to regenerate a new one.',
								'snapchat-for-woocommerce'
							) }
						</p>
					</div>
				) }
			</AccountCard>
		</>
	);
};

export default ProductCatalog;
