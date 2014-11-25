Silverstripe Utilities (Milkyway Multimedia)
======
**Silverstripe Utilities (Milkyway Multimedia)** are just some additional scripts that are required for some of our modules to function.

Includes:
- Milkyway\Assets : This is a class that gives additional functionality to the Silverstripe Requirements Engine. The new backend is automatically disabled for the administration section.
- Milkyway\Director : Some additional controller specific methods and globals

### Milkyway\SS\Assets
This adds a couple of new methods that you can use to better control the Requirements in your front-end (everything except deferring should work on the back end as well)

- Milkyway\SS\Assets::defer($file) : Defer a file (loaded after rest of content has finished downloading, using Google Async method)
- Milkyway\SS\Assets::inline($file, $top = false) : Inline a file (output contents of file directly to specific section)
- Milkyway\SS\Assets::replace($old, $new) : Replace any requirement with another requirement (you can replace the jQuery version etc.)
- Milkyway\SS\Assets::head($file) : Insert a file into the header rather than before the ending body tag
- Milkyway\SS\Assets::add(array $files, 'first/last/defer/inline/inline-head', $before = '') : Add a requirement to the page in a specific section/way.
- Milkyway\SS\Assets::remove(array $files, 'first/last/defer/inline/inline-head') : Remove a requirement (only works on those added using this interface). If you leave out the second argument, it will search all requirements and remove it
- Milkyway\SS\Assets::block_ajax($file|$id) : Block a file/script from loading via AJAX

### Milkyway\SS\Director
This extends Director and has a few utility methods related to dealing with the SiteTree and Controllers, and adds some new template globals

- secureBaseURL
- nonSecureBaseURL
- baseWebsiteURL - The url without the protocol or www, the pretty url
- protocol
- homePage
- isHomePage($page = SiteTree|int)
- adminLink
- siteConfig

### Milkyway\SS\Utilities
Some utility methods to deal with some stuff I could not do with vanilla Silverstripe, and also adds some new template globals

- canAccessCMS
- canEditCurrentPage
- inlineFile($file, $theme = true|false)
- placeIMG($width,$height,$categories = any|animals|architecture|nature|people|tech,$filters = grayscale|sepia) = Placeholder image (using placeimg.com)
- loremIpsum($paragraphs,$length = short|long) = Placeholder Text

### Shortcodes
There are some new shortcodes that have been registered (once shortcodes are controlled by YAML config, these can be disabled via that...). These are automatically plugged in to the awesome shortcodes module by sheadawson

- [site_config]
- [user]
- [google_fixurl] : Render the Google Fix URL javascript (good for 404 pages)
- [current_page]
- [icon]

## Install
Add the following to your composer.json file

```

    "require"          : {
		"milkyway-multimedia/silverstripe-mwm": "dev-master"
	}

```

## License
* MIT

## Version
* Version 0.1 - Alpha

## Contact
#### Milkyway Multimedia
* Homepage: http://milkywaymultimedia.com.au
* E-mail: mell@milkywaymultimedia.com.au
* Twitter: [@mwmdesign](https://twitter.com/mwmdesign "mwmdesign on twitter")