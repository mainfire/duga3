## Note ##

---

I have recently taken a liking to git and will be using [gitorious](http://gitorious.org/kipz-bittorrent/duga-3) for all future updates. Feel free to fork, contribute, and send a pull request for merge.

## About ##

---

Дуга-3 / Duga-3 / Arc-3 is based on another project I started called "k2". k2 was based off of something else I had done a while back. So, this would be the third incarnation, hence the [name](http://www.arrl.org/news/surfin-remembering-the-woodpecker) fitting the project again (in more than one way).

Finally commited to SVN on June 15, 2010. This initial code should be good enough to crawl a large amount of RSS feeds on torrent sites, parse and store the majority of the torrents info, and the like. I managed to get 43 sites to initially work, and included 7 plugins mostly for example purposes. It uses bz2, cURL, Dom, and MySQLi to achieve it's level of speed.

The open tracker is included as part of Duga-3, but isn't integrated into the crawler in any way. This tracker was forked off of the original [Whitsoft opentracker](http://www.whitsoftdev.com/opentracker/) code almost three years ago, and has since been almost rewritten entirely to utilize MySQLi and FULLTEXT searching heavily. Right now the tracker supports the draft "IPv6" paper from bittorrent.org, **and an unofficial extension known as "compact scraping"**.

### Recent developments ###

I have started a Drizzle port of this, with no plans to actually release it (yet).

## Current "state" of the project ##

---

February 6, 2014: **_DEAD, DOESNT WORK ANYMORE. NO PLANS TO FIX IT. SORRY!_**

June 28, 2010:
  * Crawler: _beta / stable_ (mostly _stable_)
  * Tracker: _alpha / beta_

I have also done extensive testing on FreeBSD, Linux, and Win32 installs (specifically using MySQL, nginx, and PHP each time). The only lacking feature is symlinking in the crawler (which can be disabled) for any versions of Windows below Vista - this is due to `mklink` being introduced in Vista...

## Get the code ##

---

### There are no plans to ever make any tarballed / zipped releases ###

I am using Subversion to store this project - this is _required_ in order to get the code, however Subversion is freely available on a multitude of platforms, and is very easy to use. I also wrote some instructions below for new users.

Windows users should use [Slik SVN](http://www.sliksvn.com/en/download) for the below instructions, or something besides TortoiseSVN. Everyone else should follow [this link](http://subversion.apache.org/packages.html) for instructions on installing Subversion for any given OS.

### Recommended ###

Get the entire project by running the `checkout`:

```
svn checkout http://duga3.googlecode.com/svn/trunk/ duga3
```

Since there are usually daily updates, **stay up to date** by moving your console into the directory you checked out into and run:

```
svn update
```

### DIY ###

Otherwise, if you can handle it yourself, you can also use `export` to "checkout" the entire project without the .svn folders:

```
svn export http://duga3.googlecode.com/svn/trunk/ duga3
```

If you want just the crawler:
```
cd /your/web/root/location
#example search interface
svn export http://duga3.googlecode.com/svn/trunk/index.php
#admin interface, can be ran from anywhere
svn export http://duga3.googlecode.com/svn/trunk/admin/index.php admin/index.php
#the crawler itself, make this forbidden
svn export http://duga3.googlecode.com/svn/trunk/lib/crawler lib/crawler
```

...or maybe just the tracker:

```
cd /your/web/root/location
mkdir tracker #optional
cd tracker
#client announce file
svn export http://duga3.googlecode.com/svn/trunk/announce.php
#client scrape file
svn export http://duga3.googlecode.com/svn/trunk/scrape.php
#the "stats" page you could use as an example to make a bnbt style front-end
svn export http://duga3.googlecode.com/svn/trunk/tracker.php
#the tracker itself, make this forbidden
svn export http://duga3.googlecode.com/svn/trunk/lib/opentracker lib/opentracker
```

## Additional info ##

---

### Final notes ###

_Please take note of the `README`, and `TODO` files in both [lib/crawler/](http://duga3.googlecode.com/svn/trunk/lib/crawler/README) and [lib/opentracker/](http://duga3.googlecode.com/svn/trunk/lib/opentracker/README)!_

**Known "bug" in crawler**: It's possible for fullscrape files to not get deleted, be sure to clean your CACHEDIR manually every once in a while.

### Contact ###

Thank you to everyone who has sent me positive feedback or just a thanks, but I have removed my email from this page due to increasing levels of spam. My username is on the right ("Owners"), I think you can figure out how to send me an email from there ;)