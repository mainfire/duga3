# About #

As per the outline of the [official IPv6 draft paper on bittorrent.org](http://www.bittorrent.org/beps/bep_0007.html), this has also been implemented into the opentracker software which is available via SVN.

I really don't think it is necessary to rewrite the draft page here, so just visit the link for now please :)

## Anything else? ##

I would like to make note that due to the broadness in some of the ideas it describes, I have tried my best to tie up what is loosely mentioned or went over.

In particular, we **need to do an endpoint check of some sort** on the opposite IP protocol that the request was made on. This would only apply to a (current) client such as uTorrent who would supply the IPv6 parameter to the tracker.

Finally, this _will_ return `peers6` when a client does supply an IPv6 request, either by means of a direct request from IPv6, or by supplying the IPv6 parameter in an announce request. As to my current knowledge, I do not know if any clients supply the IPv4 parameter when requesting directly from IPv6, but this is also supported and _should_ work with what was outlined in the draft paper. Last note: the current idea is to completely separate the IP protocol versions for logical / sanity reasons.