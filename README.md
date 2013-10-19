PHP Negotiator
==============

A minimal PHP content negotiation library to process common headers such as media types, encodings, charsets and so on.

Quite a few concepts are taken from (/federomero/negotiator).

Accept (Media Types)
==============

```php
require 'negotiator.php';

$negotiator = new Negotiator\Parser([
	'accept-charset' => 'utf-8, iso-8859-1;q=0.8, utf-7;q=0.2',
	'accept' => 'text/html, application/*;q=0.2, image/jpeg;q=0.8',
	'accept-language' => 'en;q=0.8, es, pt',
	'accept-encoding' => 'gzip, compress;q=0.2, identity;q=0.5',
]);

$available = ['text/html', 'text/plain', 'application/json'];

$negotiator->preferredMediaTypes();
// ['text/html', 'image/jpeg', 'application/*']

$negotiator->preferredMediaTypes($available);
// ['text/html', 'application/json']

$negotiator->preferredMediaType($available);
// 'text/html'

```

Do note that you must retrieve the headers yourself and standardise the keys to lowercase representations (e.g. 'accept' vs. 'Accept').

Methods
==============

`preferredMediaTypes($available)`

Returns an array of preferred media types ordered by priority and optionally selected from a set of available types.

`preferredMediaType($available)`

Returns a string of the highest priority media type preferred, optionally selected from a set of available types.

`preferredLanguages($available)`

Returns an array of preferred languages ordered by priority and optionally selected from a set of available languages.

`preferredLanguage($available)`

Returns a string of the highest priority language preferred, optionally selected from a set of available languages.

`preferredCharsets($available)`

Returns an array of preferred character sets ordered by priority and optionally selected from a set of available character sets.

`preferredCharset($available)`

Returns a string of the highest priority character set preferred, optionally selected from a set of available character sets.

`preferredEncodings($available)`

Returns an array of preferred encodings ordered by priority and optionally selected from a set of available encodings.

`preferredEncoding($available)`

Returns a string of the highest priority encoding preferred, optionally selected from a set of available encodings.
