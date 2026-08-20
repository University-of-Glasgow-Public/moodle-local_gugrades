# Getting Started

This doc is a basic overview to get started developing / debugging this plugin. Just for end use, it should happily install like any other Moodle plugin. 

## Overview of architecture

This is basically a normal local plugin. However, the user interface is written entirely in Vue.js. This is embedded as a complete Vue "Single Page Application" in the sub-directory 'ui'. The conventional moodle code provides...

* A bunch of web services are exposed for the Vue code to "talk" to Moodle
* lib.php adds the link to the Vue SPA to the course menu

## Vue.js requirements

Vue has extensive Node module requirements which are not included within the distribution. You'll first need Node itself installed - I recommend
using [Node Version Manager](https://github.com/nvm-sh/nvm). I installed whatever the latest LTS version of node was. 

To install all the node module requirements for Vue, navigate to the ui/ directory and type...

    npm install

...this should install all the dependencies. This is all that is required. 

IMPORTANT: For development, set $CFG->cachejs = false; in config.php. Otherwise, changes to the Vue code will not be loaded in Moodle. 

## Building Vue components

Vue has a build step. In order to to see new updates to the Vue code it must first be built. The resulting minified javascript is written to the dist/ directory. This is all completely "vanilla" Vue usage and the Vue documentation should be referred to. The Vite builder is in use - again, the recommended way to use Vue. 

The build step can be run continuously. It will look for changes and rebuild the output javascript on the fly. Go to the ui/ directory and type...

    npm run watch

This should successfully compile all the Vue code and then sit and watch for changes. 

NOTE: This does not automatically refresh the page. You need to refresh the Moodle page yourself to see changes. 