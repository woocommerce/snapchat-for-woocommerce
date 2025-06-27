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
import { ALL_ACCOUNTS } from './constants';

const textDict = {
	[ ALL_ACCOUNTS ]: {
		title: __( 'Disconnect all accounts', 'snapchat-for-woo' ),
		confirmButton: __( 'Disconnect all accounts', 'snapchat-for-woo' ),
		confirmation: __(
			'Yes, I want to disconnect all my accounts.',
			'snapchat-for-woo'
		),
		contents: [
			__(
				'I understand that I am disconnecting any WordPress.com account and Snapchat account connected to this extension.',
				'snapchat-for-woo'
			),
			__( 'Lorem ipsum', 'snapchat-for-woo' ),
			__(
				'Dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
				'snapchat-for-woo'
			),
		],
	},
};

export default function ConfirmModal( {
	disconnectTarget,
	onRequestClose,
	onDisconnected,
	disconnectAction,
} ) {
	const [ isAgreed, setAgreed ] = useState( false );
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const { disconnectAllAccounts } = useAppDispatch();

	const { title, confirmButton, confirmation, contents } =
		textDict[ disconnectTarget ];

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		let disconnect = disconnectAllAccounts;

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
					{ __( 'Never mind', 'snapchat-for-woo' ) }
				</AppButton>,
				<AppButton
					key="2"
					isPrimary
					isDestructive
					loading={ isDisconnecting }
					disabled={ ! isAgreed }
					onClick={ handleConfirmClick }
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
