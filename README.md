## ArtaxApiBuilder

Creates a library to consume an API from a Guzzle-like service description.

## Why?

* Underlying HTTP implementation in Curl/PHP streams isn't that great.

* Still a massive code base that doesn't really do that much.

* Untestable / unmockable.
 
* A service generated as code allows you to debug, extend or generally tart around with the code as necessary. Once the code is generated it's just there and completely modifiable (if you wish), rather than requiring everything being piped through Guzzle.

* Smaller memory requirement (and memory is PHP's Achilles heel) for using the generated service.

* Type-hinting all the things for great Justice.

* Better exposure of underlying mechanisms. Although Guzzle is nice doing everything for you, having direct access to the request and/or responses is a pretty powerful technique for when people fuck up their API, and you need to hack around their stupidity (Github, I'm looking at you).


## Danack/Code vs Zend/Code


Please note, the fork Danack/Code is almost exactly the same as Zend/Code, except that it has some bug fixes which will only be released in the next major version of Zend/Code. As this won't happen for the foreseeable future, this library currently uses my fork with the fixes applied. The plan is to go back to the mainstream version of Zend/Code at the next major release..