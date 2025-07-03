export function findSnaptrEvent( queue, eventName ) {
	return queue.find( ( [ command, name ] ) => {
		return command === 'track' && name === eventName;
	} ) || null;
}
