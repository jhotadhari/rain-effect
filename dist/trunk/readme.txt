=== Rain Effect ===
Tags: rain,image
Donate link: https://waterproof-webdesign.info/donate
Contributors: jhotadhari
Tested up to: 4.9
Requires at least: 4.7
Requires PHP: 5.6
Stable tag: trunk
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Let it rain


== Description ==

Apply a rain effect to your images using WebGL.

= How to use =
* Install and go to 'Appearance' -> 'Customize'
* Open Section for Header or Footer Images and enable/disable the Rain Effect.
* Or/And open Section for Rain Effect and chose when to load rain effect script.
* When the script is loaded, the effect will be applied for all images with class 'rain-effect'.
* Only for images in the media library. Doesn't work for remote Images (because we'll make an ajax request to get the thumbnail image source).
* Please note that the effect is highly experimental and might not work as expected in all browsers.


= Thanks for beautiful ressoucres
* The effect is based on [Lucas Bebbers](http://gardenestudio.com.br/) script on [Codrops](https://tympanus.net/codrops/2015/11/04/rain-water-effect-experiments/). Find it on [GitHub](https://github.com/codrops/RainEffect).
* This Plugin is generated with [generator-pluginboilerplate version 1.2.2](https://github.com/jhotadhari/generator-pluginboilerplate)


== Installation ==
Upload and install this Theme the same way you'd install any other Theme.
And go to 'Appearance' -> 'Customize'


== Screenshots ==


== Upgrade Notice ==



# 
== Changelog ==

## 0.1.3 - 2023-09-03
Fix didn't start raining

### Fixed
- Didn't start raining. Remove window on load event and just start when DOM is ready

## 0.1.2 - 2023-09-03
Fix Rain_Effect_Loader

## 0.1.1 - 2023-09-03
Updated dependencies

### Changed
- Updated to generator-wp-dev-env#1.6.8 ( wp-dev-env-grunt#1.6.2 wp-dev-env-frame#0.16.0 )

## 0.1.0 - 2020-04-20
Update Dependencies

### Changed
- Updated to generator-wp-dev-env#0.14.2 ( wp-dev-env-grunt#0.9.7 wp-dev-env-frame#0.8.0 )
