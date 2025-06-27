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
import { useSnapchatConnectFlow } from '~/components/snapchat-account-card';
import AppDocumentationLink from '../app-documentation-link';

/**
 * Renders a card to connect to Snapchat Account.
 *
 * Please note that this component is only used on the onboarding flow.
 *
 * @param {Object} props React props
 * @param {boolean} [props.disabled] Whether display the Card in disabled style.
 */
const ConnectSnapchatComboAccountCard = ( { disabled } ) => {
	const pageName = 'setup';
	const [ handleConnect, { loading, data } ] =
		useSnapchatConnectFlow( pageName );
	const [ termsAccepted, setTermsAccepted ] = useState( false );

	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			disabled={ disabled }
			alignIcon="top"
			className="sfw-snapchat-combo-service-account-card--snapchat"
			description={
				<>
					<p>
						{ __(
							'Connect your Snapchat Business Account to sync your catalog and run Dynamic Ads.',
							'snapchat-for-woo'
						) }
					</p>
					<CheckboxControl
						label={ createInterpolateElement(
							__(
								'I accept the terms and conditions of <linkMerchant>Snap</linkMerchant> and <linkAds>Chat</linkAds>',
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
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					disabled={ disabled || ! termsAccepted }
					loading={ loading || data }
					eventName="sfw_snapchat_account_connect_button_click"
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

export default ConnectSnapchatComboAccountCard;
