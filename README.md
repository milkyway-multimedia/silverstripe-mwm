Silverstripe Utilities (Milkyway Multimedia)
============================================
Just some additional scripts that are required for some of our modules to function.

Includes:
- singleton('require') : This is a class that gives additional functionality to the Silverstripe Requirements Engine.
- singleton('message') : Add the ability to add a global notification to any controller
- singleton('mwm') : Some additional methods to help with placeholders etc and adds new globals
- singleton('director') : Some additional controller specific methods and globals
- singleton('env') : Allow more flexible configuration, using $_ENV variables and defaulting to DataObjects. Used for a few of my modules.
- singleton('s') : Some string methods courtesy of the Stringy plugin. Used for a few methods.

### singleton('require')
This adds a couple of new methods that you can use to better control the Requirements in your front-end.

- singleton('require')->defer($file) : Defer a file (loaded after rest of content has finished downloading, using Google Async method)
- singleton('require')->inline($file, $top = false) : Inline a file (output contents of file directly to specific section)
- singleton('require')->replace($old, $new) : Replace any requirement with another requirement (you can replace the jQuery version etc.)
- singleton('require')->head($file) : Insert a file into the header rather than before the ending body tag
- singleton('require')->add(array $files, 'first/last/defer/inline/inline-head', $before = '') : Add a requirement to the page in a specific section/way.
- singleton('require')->remove(array $files, 'first/last/defer/inline/inline-head') : Remove a requirement (only works on those added using this interface). If you leave out the second argument, it will search all requirements and remove it
- singleton('require')->block_ajax($file|$id) : Block a file/script from loading via AJAX
- singleton('require')->before($files, $after) : Insert an item before a specific requirement
- singleton('require')->after($files, $after) : Insert an item after a specific requirement

### singleton('message')
This is a bit special to use, to add area specific pages you have to prepend your calls with the area you would like to add it to. For example:

```
    singleton('message')->cms()->add('Yay! My pretty messages');
```

If you want to add more, you can chain but you always must prepend the area.

```
    singleton('message')->cms()->add('Yay! My pretty messages')->cms()->add('A bad message', 'bad');
```

- singleton('message')->add($content, $level, $timeout, $priority, $dismissable, $area) : Add a global message
- singleton('message')->add($params) : Add a global message by array (you can add an id if you use this method)
- singleton('message')->remove($content, $level, $area) : Remove a global message
- singleton('message')->remove($params) : Remove a global message by array (you can remove by id if you use this method)
- singleton('message')->before($link) : Add a link to call before displaying notifications (for calls to APIs)

#### Available areas
Areas are mapped to controllers. The following areas are available:

- cms: Will add a global message to the CMS
- page: Will add a global message to any Page
- form: Will add a global message during a form request

By default the page are is used. Any other value will add a message to any controller. So you can use ->global() to add a global message.

#### How it works
This messaging system is javascript based. It should work on most controllers that access Silverstripe requirements. I am working on a modal and growl system that should plug into this.

### singleton('director')
This extends Director and has a few utility methods related to dealing with the SiteTree and Controllers, and adds some new template globals

- secureBaseURL
- nonSecureBaseURL
- baseWebsiteURL - The url without the protocol or www, the pretty url
- protocol
- homePage
- isHomePage($page = SiteTree|int)
- adminLink
- siteConfig

### singleton('mwm')
Some utility methods to deal with some stuff I could not do with vanilla Silverstripe, and also adds some new template globals

- canAccessCMS
- canEditCurrentPage
- inlineFile($file, $theme = true|false)
- placeIMG($width,$height,$categories = any|animals|architecture|nature|people|tech,$filters = grayscale|sepia) = Placeholder image (using placeimg.com)
- loremIpsum($paragraphs,$length = short|long) = Placeholder Text

### Shortcodes
There are some new shortcodes that have been registered (once shortcodes are controlled by YAML config, these can be disabled via that...). These are automatically plugged in to the awesome shortcodes module by sheadawson

- [setting field=Title] : Display a field from Site Settings
- [user field=Name default=Guest] : Display a field from the current logged in member
- [google_fixurl] : Render the Google Fix URL javascript (good for 404 pages)
- [current_page field=Title] : Display a field from the current page
- [icon]mwm[/icon] : Use an icon (defaults to the font awesome set)

## Install
Add the following to your composer.json file

```

    "require"          : {
		"milkyway-multimedia/ss-mwm": "dev-master"
	}

```

## License
* MIT

## Version
* Version 0.3 (Alpha)

## Contact
#### Milkyway Multimedia
* Homepage: http://milkywaymultimedia.com.au
* E-mail: mell@milkywaymultimedia.com.au
* Twitter: [@mwmdesign](https://twitter.com/mwmdesign "mwmdesign on twitter")