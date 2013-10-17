Final version of the GHouse site / dobismaster.
------------------------------------------------------------------

URL routes & actions can be found in:
src > lib > GHouse > Controller > Provider

Page templates and CSS can be found in, respectively:
view > template &
view > scss > ui

Please note that the site's due for a good deal of refactoring before we start extending and rebranding for the Destroyer prototype. Things are relatively well organized, but there are some specific things I'd like to tackle before extending:

1. Middlewares should be consolidated into a middleware provider class independent of the controller providers. The BaseControllerProvider should be modified to autoload the correct middlewares from the middleware provider class.

2. Form processing could be delegated to its own service provider class or set of classes so the DobisMasterContollerProvider class isn't so bloated.

3. The CSS should be organized a little better. There are some redundant styles that should be abstracted and extended rather than repeated. The class names loosely follow BEM syntax (http://csswizardry.com/2013/01/mindbemding-getting-your-head-round-bem-syntax/) but could be more consistent.

4. There are a few services defined in app.php that should really be in a "Tools" or "Helper" class rather than lumped into the app's bootstrap. (Slugify, old slugify, & a number-to-grid-width function).

5. Require.js should be linked to a YUI compressor.

6. The production site should use an HTTP cache.
