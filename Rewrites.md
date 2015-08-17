# nginx #

```
rewrite ^/announce([^/]+)?$ /announce.php$1 last;
rewrite ^/scrape([^/]+)?$ /scrape.php$1 last;
```