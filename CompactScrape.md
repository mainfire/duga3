# About #

This is a _very_ unofficial extension to the tracker "scrape" implementation. It works very much like the already widely supported compact announce.

## How does it work exactly? ##

This is a fairly simple idea that is mostly based on the idea of packing data, like a compact announce. To review and make sure you know what a compact announce is:

  * IP Address of the end user (unsigned long - 32bit, big endian)
  * Port of the torrent client (unsigned short - 16bit, big endian)

After packing into the specified formats, the returned data is much smaller compared to the full bencoded response.

The only real difference from here is the data we are packing, and the way we are returning this data to the client in a scrape. Instead of packing an IP and port, we will pack the swarm information (in the mentioned order):

  * complete
  * incomplete
  * downloaded

Each of the above would be packed as unsigned short (16 bit, big endian).

## An example in bdecoded arrays ##

As an example, here's a regular fullscrape response containing two (invalid, for example purposes) infohashes:

```
Array
(
	[files] => Array
	(
		[binaryhash1^] => Array
		(
			[complete] => 0
			[downloaded] => 0
			[incomplete] => 1
		)
		[binaryhash2^] => Array
		(
			[complete] => 1
			[downloaded] => 1
			[incomplete] => 0
		)
	)
	[flags] => Array
	(
		[compact_scrape] => 1 #just an extra flag to signal we support the below method
		[min_request_interval] => 5400
	)
)
```

...and the same thing, only this time with the compact scrape:

```
Array
(
	[files] => Array
	(
		[binaryhash1^] => bytes^
		[binaryhash2^] => bytes^
	)
	[flags] => Array
	(
		[compact_scrape] => 1
		[min_request_interval] => 5400
	)
)
```

^ = binary data

This usually shaves around 40 bytes off of each infohash returned in a bencoded fullscrape. In the end, this could add up to quite the amount of saved bandwidth.

## Why would anyone need this? ##

While that is a perfectly reasonable question, my response is also very reasonable: a _very_ large majority of clients _always_ make compact announces whenever possible.

It's not required that you do this, but it helps get a faster response from the tracker, and also helps to minimize overall bandwidth use over time.

Tracker software already needs to be able to handle more than just "a few hundred" clients (and especially when you already need to bencode huge fullscrapes for indexers) - this packing data idea really shouldn't be a huge issue to implement or difficult to work with in the real world. An admin running a tracker which only watches over say, something like 1000 torrents, could easily result in a few Gigs of overall traffic every month. So generally, the idea of using the minimum amount of bandwidth required to function correctly is the key to success when coming up with new ideas like this.

## Anything else? ##

This has already been implemented this into the opentracker software, which is available via SVN. The idea is also public domain, I don't really see any reason to stake a claim in this - the more software that supports this, the better :)

Plus, I'm sure someone has came up with this by now. If they haven't, then OMGWTFBBQ