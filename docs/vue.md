# Vue.js

The [Vue.js](https://vuejs.org/) Javascript framework has been used to implement the user interface of the project. Vue requires a build stage and this is handled by Vite.
Vite is the default build application as described in the Vue docs.

The Vue portion lives entirely within the 'ui/' subdirectory.

Note that Vue requires a build step to create its final code. This is normally done using a built-in node-based web server but as we are already have Moodle running under a web server,
this has been done slightly differently. The package.json file has an additional 'command' added, 'watch' this runs the Vue production build step but still watches for code changes -
that is, whenever Vue javascript is saved the package will be rebuilt.

In order to access this, on the command line cd to the ui/ directory and run.

```
npm run watch
```

...this should build the Vue application and then sit and watch for changes.

This builds the Vue application and (assuming no errors) creates output in the ui/dist/ directory. We depart here from normal Vue practice to make this work with Moodle. The highlights are
as follows:
* The Moodle index.php (which the Moodle menu links to) is stored in the ui/dist/ directory. In order to prevent this being overridden on build (as would happen in 'normal' Vue), the
vite.config.js file has been modified to specify 'emptyOutDir' as false.
* The entire js data is minified into the file ui/dist/assets/entry.js. To get this into our Moodle application, this is 'included' within the index.php file
by 'echoing' a script statement. A shameless bodge.
* Similarly, the custom CSS fom the Vue application is output to ui/dist/style.css and is included within the index.php page.
* The remainder of the index.php file is just standard Moodle.

## Accessing Moodle resources

Although completely vanilla Moodle web services are used. A custom client (js/moodlefetch.ts) has been coded in the Vue application which accesses a custom endpoint (ajax.php) in the Moodle
plugin. This is because it's difficult to access the standard Moodle 'FetchMany' client from the Vue.application.

## Styling

The Vue application uses entirely TailwindCSS version 4 for styling. This has a Vue plugin and is part of the build step. There is some additional custom CSS in src/assets/MyGrades.css. In particular, the 
variables for the University theme are defined here. These colours are used throughout the application. Note that Tailwind 4 does not have a config file (a lot of documentation refers to a config file)

## Vite settings

The vite build settings are in vite.config.ts. There are quite a lot of settings in here. Most of it is fairly obvious or documented elsewhere. The native build behaviour is to minify and compress
the outputted javascript but this is bad for debugging. This tends to be switched off but will increase loading time in production. 

## Vue packages

We use quite a few third party packages for various features. Here are some of the more interesting ones (and why)

* [Formkit](https://formkit.com/) is used for all the HTML forms. Provides validation and so on
* [HeadlessUI](https://headlessui.com/) an extension for Tailwind that provides highly accessible unstyled components
* [VueModal](https://github.com/kouts/vue-modal) modal popup
* [Lucide for Vue](https://lucide.dev/guide/vue/) icon library
* [Web update notification](https://github.com/GreatAuk/plugin-web-update-notification) detects a new version of the Vue app and notifies user
* [TailwindCSS](https://tailwindcss.com/) CSS library
* [Tanstack Table](https://tanstack.com/table/latest) Headless data tables. Used for all tables.
* [VueUse](https://vueuse.org/) Lots of things, but we only use it for some file handling functions
* [KY](https://github.com/sindresorhus/ky) REST client.
* [v-tour-guide](https://github.com/whytepeter/v-tour-guide) Used for initial user tour

## References

* [Vue Toatification](https://vue-toastification.maronato.dev/) is used to render notifications
* [FormKit](https://formkit.com/) is used to render form elements
* [Vue3 Easy Data Table](https://github.com/HC200ok/vue3-easy-data-table) used to render tables