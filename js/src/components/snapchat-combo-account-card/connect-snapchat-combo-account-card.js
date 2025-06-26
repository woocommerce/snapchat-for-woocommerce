/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import {
	ReadMoreLink,
	useGoogleConnectFlow,
} from '~/components/snapchat-account-card';
import AppDocumentationLink from '../app-documentation-link';

/**
 * Renders a card to connect to Google Account.
 *
 * Please note that this component is only used on the onboarding flow.
 *
 * @param {Object} props React props
 * @param {boolean} [props.disabled] Whether display the Card in disabled style.
 */
const ConnectGoogleComboAccountCard = ( { disabled } ) => {
	const pageName = 'setup-mc';
	const [ handleConnect, { loading, data } ] =
		useGoogleConnectFlow( pageName );
	const [ termsAccepted, setTermsAccepted ] = useState( false );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			disabled={ disabled }
			alignIcon="top"
			className="sfw-google-combo-service-account-card--google"
			description={
				<>
					<p>
						{ __(
							'Required to sync with Google Merchant Center and Google Ads.',
							'snapchat-for-woo'
						) }
					</p>
					<CheckboxControl
						label={ createInterpolateElement(
							__(
								'I accept the terms and conditions of <linkMerchant>Merchant Center</linkMerchant> and <linkAds>Google Ads</linkAds>',
								'snapchat-for-woo'
							),
							{
								linkMerchant: (
									<AppDocumentationLink
										context="setup-mc-accounts"
										linkId="google-mc-terms-of-service"
										href="https://support.google.com/merchants/answer/160173"
									/>
								),
								linkAds: (
									<AppDocumentationLink
										context="setup-ads"
										linkId="google-ads-terms-of-service"
										href="https://support.google.com/adspolicy/answer/54818"
									/>
								),
							}
						) }
						checked={ termsAccepted }
						onChange={ setTermsAccepted }
						disabled={ disabled }
					/>
				</>
			}
			helper={ createInterpolateElement(
				__(
					'You will be prompted to give WooCommerce access to your Google account. Please check all the checkboxes to give WooCommerce all required permissions. <link>Read more</link>',
					'snapchat-for-woo'
				),
				{
					link: ReadMoreLink,
				}
			) }
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					disabled={ disabled || ! termsAccepted }
					loading={ loading || data }
					eventName="sfw_google_account_connect_button_click"
					eventProps={ {
						context: pageName,
						action: 'authorization',
					} }
					text={ __( 'Connect', 'snapchat-for-woo' ) }
					onClick={ handleConnect }
				/>
			}
		/>
	);
};

export default ConnectGoogleComboAccountCard;
