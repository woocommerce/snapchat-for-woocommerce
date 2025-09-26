/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';
import { recordSfwEvent } from '~/utils/tracks';
import './index.scss';

/**
 * When Conversion tracking setting is toggled.
 *
 * @event sfw_conversion_tracking_toggle
 * @property {"on"|"off"} status The status of the setting.
 */

/**
 * When Collect PII setting is toggled.
 *
 * @event sfw_collect_pii_toggle
 * @property {"on"|"off"} status The status of the setting.
 */

/**
 * ConversionsAPI component for managing the tracking setting.
 *
 * Renders a card UI allowing users to enable or disable server-side conversion event tracking.
 * Handles asynchronous state updates and displays success notifications upon status change.
 * Shows a loading spinner while the current tracking status is being resolved.
 *
 * @fires sfw_conversion_tracking_toggle
 * @fires sfw_collect_pii_toggle
 *
 * @return {JSX.Element} The rendered ConversionsAPI settings card.
 */
const ConversionsAPI = () => {
	const { capiEnabled, collectPii, hasFinishedResolution } = useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateSettings } = useAppDispatch();

	const toggleTrackConversions = useCallback( async () => {
		const {
			settings: { capiEnabled: __capiEnabled },
		} = await updateSettings( { capiEnabled: ! capiEnabled } );

		recordSfwEvent( 'sfw_conversion_tracking_toggle', {
			status: __capiEnabled ? 'on' : 'off',
		} );
	}, [ updateSettings, capiEnabled ] );

	const toggleCollectPii = useCallback( async () => {
		const {
			settings: { collectPii: __collectPii },
		} = await updateSettings( { collectPii: ! collectPii } );
		recordSfwEvent( 'sfw_collect_pii_toggle', {
			status: __collectPii ? 'on' : 'off',
		} );
	}, [ updateSettings, collectPii ] );

	const handleOnChangeOfConversionTracking = async () => {
		try {
			setIsSaving( true );
			await toggleTrackConversions();

			createNotice(
				'success',
				__(
					'Conversions API Tracking status updated successfully.',
					'snapchat-for-woocommerce'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateSettings` action.
		} finally {
			setIsSaving( false );
		}
	};

	const handleOnChangeOfCollectPii = async () => {
		try {
			setIsSaving( true );
			await toggleCollectPii();

			createNotice(
				'success',
				__(
					'Collect PII status updated successfully.',
					'snapchat-for-woocommerce'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateSettings` action.
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return (
		<AccountCard
			className="sfw-settings-track-conversions"
			title={ __( 'Conversions API', 'snapchat-for-woocommerce' ) }
			description={ __(
				'Send server-side conversion events to improve attribution.',
				'snapchat-for-woocommerce'
			) }
			actions={
				<div className="sfw-settings-track-conversions__actions">
					<p>
						<CheckboxControl
							label={ __(
								'Enable Conversions API tracking',
								'snapchat-for-woocommerce'
							) }
							checked={ capiEnabled }
							disabled={ isSaving }
							onChange={ handleOnChangeOfConversionTracking }
						/>
					</p>

					<p>
						<CheckboxControl
							label={ __(
								'Collect Customer PII',
								'snapchat-for-woocommerce'
							) }
							checked={ collectPii }
							disabled={ isSaving }
							onChange={ handleOnChangeOfCollectPii }
							help={ __(
								'Share additional customer data (PII) with both Pixel and Conversions API events to improve ads measurement.',
								'snapchat-for-woocommerce'
							) }
						/>
					</p>
				</div>
			}
		/>
	);
};

export default ConversionsAPI;
