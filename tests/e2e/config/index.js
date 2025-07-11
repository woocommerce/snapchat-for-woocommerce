const admin = {
	username: 'admin',
	password: 'password',
};

const customer = {
	username: 'customer',
	password: 'password',
	billing: {
		firstName: 'John',
		lastName: 'Doe',
		company: 'Automattic',
		country: 'US',
		countryName: 'United States',
		address: 'addr 1',
		addressSecondLine: 'addr 2',
		city: 'San Francisco',
		state: 'CA',
		stateName: 'California',
		zip: '94107',
		phone: '123456789',
		email: 'john.doe@example.com',
	},
};

module.exports = {
	admin,
	customer,
};
