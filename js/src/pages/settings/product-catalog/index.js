/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import {
	createInterpolateElement,
	useState,
	useEffect,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import AccountCard from '~/components/account-card';
import Heartbeat from './heartbeat';
import useProductCatalogExport from './useProductCatalogExport';
import './index.scss';

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
 * @return {JSX.Element} The rendered ProductCatalog settings UI.
 */
const ProductCatalog = () => {
	// Whether we want to connect the heartbeat immediately as soon as the Heartbeat component mounts.
	const [ connectHearbeatNow, setConnectHeartbeatNow ] = useState( false );
	const [ exportInProgress, setExportInProgress ] = useState(
		sfwData.isExportInProgress === '1'
	);
	const [ fileUrl, setFileUrl ] = useState( sfwData.exportFileUrl || null );
	const [ lastExported, setLastExported ] = useState(
		sfwData.lastTimestamp || null
	);

	// Trigger a heartbeat connection as soon as we get a successfull response from the server
	// when the user clicks on the "Regenerate CSV" button.
	const onGenerateCsvSuccess = () => {
		setConnectHeartbeatNow( true );
	};

	// If the CSV generation fails, we reset the state to ensure the UI reflects that no export is in progress.
	// This prevents the UI from showing a download link or last exported timestamp when there is no valid export.
	// It also stops the heartbeat connection to avoid unnecessary requests.
	const onGenerateCsvError = () => {
		setConnectHeartbeatNow( false );
		setExportInProgress( false );
		setFileUrl( null );
		setLastExported( null );
	};

	const { generateCsv } = useProductCatalogExport(
		onGenerateCsvSuccess,
		onGenerateCsvError
	);

	const handleOnRegenerateCsvClick = () => {
		generateCsv();
		setExportInProgress( true );
	};

	const handleOnRegenerateCsvCompleted = ( response ) => {
		setExportInProgress( false );

		setFileUrl( response.fileUrl );
		setLastExported( response.lastExport );
	};

	const getDescription = () => {
		if ( ! lastExported ) {
			return null;
		}

		return sprintf(
			// translators: %s: The date and time when the product catalog was last exported.
			__( 'Last exported on %s.', 'snapchat-for-woo' ),
			lastExported
		);
	};

	useEffect( () => {
		if ( ! exportInProgress ) {
			return;
		}

		setFileUrl( null );
		setLastExported( null );
	}, [ exportInProgress ] );

	return (
		<>
			{ exportInProgress && (
				<Heartbeat
					onCompleted={ handleOnRegenerateCsvCompleted }
					connectNow={ connectHearbeatNow }
				/>
			) }

			<AccountCard
				className="sfw-product-catalog"
				title={ __( 'Export Product Catalog', 'snapchat-for-woo' ) }
				description={ getDescription() }
				indicator={
					<Flex spacing={ 4 } wrap="wrap">
						<AppButton
							variant="secondary"
							onClick={ handleOnRegenerateCsvClick }
							loading={ exportInProgress }
						>
							{ __( 'Regenerate CSV', 'snapchat-for-woo' ) }
						</AppButton>
						<AppButton
							variant="primary"
							href={ fileUrl }
							disabled={ ! fileUrl }
							download
						>
							{ __( 'Download CSV', 'snapchat-for-woo' ) }
						</AppButton>
					</Flex>
				}
			>
				<div className="sfw-product-catalog__help">
					<p>
						{ __(
							'You can download the latest CSV or regenerate it if you’ve made changes.',
							'snapchat-for-woo'
						) }
					</p>
					<p>
						{ createInterpolateElement(
							__(
								'Need help? Learn how to <link>upload</link> your CSV to Snapchat.',
								'snapchat-for-woo'
							),
							{
								link: (
									<AppDocumentationLink
										context="settings"
										linkId="csv-learn-more"
										href="https://tbd"
									/>
								),
							}
						) }
					</p>
				</div>
			</AccountCard>
		</>
	);
};

export default ProductCatalog;
