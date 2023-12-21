Encryptable, an ecryptable trait for Laravel
=============================================

Encryptable is a trait for Laravel that adds simple encryptable functions to Eloquent Models.

Encryptable allows you to encrypt data as in enters the database and decrypts it on its retrieval.

# Installation

Simple add the package to your `composer.json` file and run `composer update`.

```
"gregoryduckworth/encryptable": "1.*",
```

# Usage

Add the trait to your model and your encryptable rules.

```php
use GregoryDuckworth\Encryptable\EncryptableTrait;

class User extends Authenticatable
{
	use EncryptableTrait;

	/**
	 * Encryptable Rules
	 *
	 * @var array
	 */
	protected $encryptable = [
		'name',
		'email',
	];
	
...
}
```

Now, whenever you interact with the model, the `name` and `email` will automatically be encrypted and decrypted between your frontend and the database.

## Contributing

Anyone is welcome to contribute. Fork, make your changes and then submit a pull request.

