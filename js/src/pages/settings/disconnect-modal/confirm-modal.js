/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import WarningIcon from '~/components/warning-icon';
import { useAppDispatch } from '~/data';
import { ALL_ACCOUNTS, SNAPCHAT_ACCOUNT } from './constants';

const textDict = {
	[ ALL_ACCOUNTS ]: {
		title: __( 'Disconnect all accounts', 'snapchat-for-woocommerce' ),
		confirmButton: __(
			'Disconnect all accounts',
			'snapchat-for-woocommerce'
		),
		confirmation: __(
			'Yes, I want to disconnect all my accounts.',
			'snapchat-for-woocommerce'
		),
		contents: [
			__(
				'I understand that I am disconnecting any WordPress.com account and Snapchat account connected to this extension.',
				'snapchat-for-woocommerce'
			),
			__( 'Lorem ipsum', 'snapchat-for-woocommerce' ),
			__(
				'Dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
				'snapchat-for-woocommerce'
			),
		],
	},
	[ SNAPCHAT_ACCOUNT ]: {
		title: __( 'Disconnect Snapchat account', 'snapchat-for-woocommerce' ),
		confirmButton: __(
			'Disconnect Snapchat Account',
			'snapchat-for-woocommerce'
		),
		confirmation: __(
			'Yes, I want to disconnect my Snapchat account.',
			'snapchat-for-woocommerce'
		),
		contents: [
			__(
				'I understand that I am disconnecting my Snapchat account from this WooCommerce extension.',
				'snapchat-for-woocommerce'
			),
			__(
				'Some configurations for Snapchat created through WooCommerce may be lost. This cannot be undone.',
				'snapchat-for-woocommerce'
			),
		],
	},
};

/**
 * When the confirm "Disconnect Snapchat account" button is clicked inside the modal.
 *
 * @event sfw_disconnect_snapchat_confirm_modal_button_click
 */

/**
 * @fires sfw_disconnect_snapchat_confirm_modal_button_click
 */
export default function ConfirmModal( {
	disconnectTarget,
	onRequestClose,
	onDisconnected,
	disconnectAction,
} ) {
	const [ isAgreed, setAgreed ] = useState( false );
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const dispatcher = useAppDispatch();

	const { title, confirmButton, confirmation, contents } =
		textDict[ disconnectTarget ];

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		let disconnect =
			disconnectTarget === ALL_ACCOUNTS
				? dispatcher.disconnectAllAccounts
				: dispatcher.disconnectSnapchatAccount;

		if ( disconnectAction ) {
			disconnect = disconnectAction;
		}

		setDisconnecting( true );
		disconnect()
			.then( () => {
				onDisconnected();
				onRequestClose();
			} )
			.catch( () => {
				setDisconnecting( false );
			} );
	};

	return (
		<AppModal
			className="sfw-disconnect-accounts-modal"
			title={
				<>
					<WarningIcon size={ 20 } />
					{ title }
				</>
			}
			isDismissible={ ! isDisconnecting }
			buttons={ [
				<AppButton
					key="1"
					isSecondary
					disabled={ isDisconnecting }
					onClick={ handleRequestClose }
				>
					{ __( 'Never mind', 'snapchat-for-woocommerce' ) }
				</AppButton>,
				<AppButton
					key="2"
					isPrimary
					isDestructive
					loading={ isDisconnecting }
					disabled={ ! isAgreed }
					onClick={ handleConfirmClick }
					eventName="sfw_disconnect_snapchat_confirm_modal_button_click"
				>
					{ confirmButton }
				</AppButton>,
			] }
			onRequestClose={ handleRequestClose }
		>
			{ contents.map( ( text, idx ) => (
				<p key={ idx }>{ text }</p>
			) ) }
			<CheckboxControl
				label={ confirmation }
				checked={ isAgreed }
				disabled={ isDisconnecting }
				onChange={ setAgreed }
			/>
		</AppModal>
	);
}
