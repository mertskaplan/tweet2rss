# tweet2rss

`tweet2rss` is a lightweight PHP application that converts tweets from a specified X (formerly Twitter) account into an RSS feed. It fetches tweets from [xstalk.com](http://xstalk.com) and generates an RSS 2.0 feed in real-time.

This tool is perfect for users who want to follow X accounts through RSS readers instead of directly using the X platform.

---

## Features

- Fetch tweets from any public X account
- Convert relative time strings (e.g., "5 min ago", "2 days ago") to proper RSS dates
- Generate valid RSS 2.0 XML output
- Handle errors gracefully if the account does not exist or cannot be fetched
- Output directly in browser or as downloadable RSS feed
- Lightweight: pure PHP with no database required

---

## Installation

1. Clone this repository to your server or local environment:

```bash
git clone https://github.com/mertskaplan/tweet2rss.git
```

1. Make sure PHP 7+ is installed and accessible.
2. Place the files in your web server directory (e.g., /var/www/html/tweet2rss).

## Usage

Open your browser and access the script with the `account` query parameter: `http://yourserver/tweet2rss/index.php?account=mertskaplan`

- Replace `mertskaplan` with the X username you want to fetch tweets from.
- The output is a valid RSS feed that can be used in any RSS reader.

---

## Demo

You can visit the following address to try the **tweet2rss** application live: <https://lab.mertskaplan.com/tweet2rss/?account=mertskaplan>

---

## Example

```xml
<rss version="2.0">
  <channel>
    <title>X Feed for @mertskaplan</title>
    <link>https://x.com/mertskaplan</link>
    <description>Tweets of account X with username mertskaplan.</description>
    <lastBuildDate>Mon, 27 Oct 2025 23:45:00 GMT</lastBuildDate>
    <generator>tweet2rss by mertskaplan</generator>

    <item>
      <title>Hello World...</title>
      <link>https://x.com/mertskaplan/status/1234567890</link>
      <guid isPermaLink="true">https://x.com/mertskaplan/status/1234567890</guid>
      <description>Hello World from my first tweet!</description>
      <pubDate>Mon, 27 Oct 2025 22:30:00 GMT</pubDate>
    </item>

  </channel>
</rss>
```

---

## Notes

- Tweets are fetched via [xstalk.com](http://xstalk.com), which provides a simple HTML view of public X profiles.
- The script parses tweets and times from the HTML and converts them into RSS format.
- Relative times like `[5 min ago]` or `[Jan 1, 2024]` are automatically converted to standard RSS date format.

---

## License

This project is licensed under the **MIT License**. See [LICENSE](https://github.com/mertskaplan/tweet2rss/blob/main/LICENSE)

---

## Contact

For questions or suggestions, contact **Mert S. Kaplan** at [mail@mertskaplan.com](mailto:mail@mertskaplan.com)
