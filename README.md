# Simplified

This is an example application to help you start accepting payments with Simplify Commerce by MasterCard.

It was created with open-source tools, and provides a barebones implementation of the Simplify Commerce PHP SDK and SimplifyJS for tokenization. Using built in development tools, and reference matierial you can begin accepting payments using Simplify Commerce by following the three step setup guide.

## Features
* Fully Responsive
* Interactive Test Suite
* Inline Reference Guide
* Based on Bootstrap by Twitter
* Easy Configuration

## Documentation

1. Copy your API keys from Simplify.com (Make sure you use your sandbox keys!)
2. Open index.php in any text editor.
3. Paste the keys between the quotes:

```php
<?php
$simplified = array(
     'publicKey' => 'YOUR_PUBLIC_KEY',
     'privateKey' => 'YOUR_PRIVATE_KEY'
);
// That's it, Simplify, simplified.
```

## Customization

Once you have had a chance to explore, fill in your details below. This will transform your application, removing the included guides/references, replacing copy with your information, leaving you with a simple public payment form.

```php
$simplified['title']        = 'Simplified';
$simplified['description']  = 'Simplified is an example application...';
$simplified['company']      = 'Simplified, Inc.';
$simplified['amount']       = '2500'; // $25.00
```

## License
This software is Open Source, released under the BSD 3-Clause license. See [LICENSE.md](LICENSE.md) for more info.