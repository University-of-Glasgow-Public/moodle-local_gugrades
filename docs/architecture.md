# Introduction and Goals



## Requirements Overview

This describes the requirements for the staff-facing side of the new GCAT development.

## General description

The code is organised as a conventional Moodle 'local' plugin. The facilities in lib.php are used to tie the plugin into the Moodle navigation. It appears in the 'More..' tab/menu of each Moodle course. This link maps to the index.php file which is located in the ui/dist folder (more about this later) with a single parameter - the course id.

The user interface is coded in ['Vue.js'](https://vuejs.org/), in particular it utlises Vue's standard build tool, Vite.  See the [vue.md](vue.md) file for more details. The Vue (UI) part is essentially a completely separate application separating the Moodle plugin "backend" with the Vue.js user interface. The two are linked using web services. 

The Moodle plugin's lib.php file creates the link under More.. on the course page to Moodle. This is a link to Vue.js's normal index.html file generated in Vue's dist/folder.

As Vue runs entirely client-side, its interaction with Moodle is exclusively via web services. Normal Moodle web service calls are used, although a custom rest client/server is created to avoid Moodle's amd javascript requirement. Each custom web service is given its own class in 'classes/external'. Most of the non-UI processing originates with these web services.

The classes/ directory also contains a number of classes that contain static shared 'service' functions. These functions should be comprehensively commented.

# Organisation of code

* The plugin is written as a Moodle "local plugin". See https://moodledev.io/docs/apis/plugintypes/local
* The basic organisation is to code the user interface as a standalone Vue.js application and the "business logic" in PHP.
* The user interface is primarily developed using the Vue 3 javascript library and Vite tooling
* The user interface access the backend using REST web services. This leverages Moodle's standard web service library. See https://moodledev.io/docs/apis/subsystems/external
* So as to avoid trying to combine Vue with Moodle's AMD modules, a custom web service client and endpoint have been coded. This is mostly stolen from the PDF grading screen in Assignment which works in a very similar way.

### Linking to moodle

The plugin uses Moodle standard navigation APIs to add the link to 'UofG Grades' into each course's "More..." menu.

# Detailed code layout

## "Backend" implementation

The backend / business logic is written as PHP classes and is primarily exposed through normal Moodle external APIs (web services). These are supported by static service classes for commonly/repeatedly used functions.

Each API function is accessible in (mostly) two places. The parameters etc. are the same. They can all be accessed either as a static method in classes/api.php or by a Moodle web service. Note that Moodle web services are self-documenting (see developer docs)

The web services are defined in db/services.php and as separate classes in classes/external. Each is self-documenting in the normal Moodle manner.

Although these services are completely normal Moodle web services (and can be used as such) the Vue UI part does not tdo that. A custom ajax.php file has been provided in the MyGrades plugin as the endpoint for webservice calls. This, in turn, links to normal Moodle web service internal functionality. 

## Activity classes

In order to interface with different activies (and grade) types, a set of classes have been created. These are implemented such that if a class exists for a specific activity (e.g. Assignment) then that class will be instantiated. If no class exists then a 'default' class is instantiated giving "lowest common demoninator" functionality. A special case is manual grades which have their own class. The classes implement an interface, [activity_interface](../classes/activities/activity_interface.php)

Current implemented classes are as follows

| Class name              | Description
|-------------------------|--------------------------------------|
| [assign_activity](../classes/activities/assign_activity.php) | Assign activity class |
| [manual](../classes/activities/manual.php) | Special case for handling manual grade items |
| [default_activity](../classes/activities/default_activity.php) | Used if no other class exists |


## User interface

The user interface is written entirely in Vue.js.  This is effectively allows new HTML elements to be created as what Vue calls "components". These contain all logic and presentation to render that element.
Properties and events are fully supported.

This implies a hierarchy of functionality. The components leverage Moodle ajax calls to interact with the backend.


# Logging

Standard Moodle event logging is used for critical functions. This records "Person X did Y" type events.

| Event               | Description
|---------------------|--------------------|
| [import_grades_users](../classes/event/import_grades_users.php) | Grade import button has been pressed |
| [view_gugrades](../classes/event/view_gugrades.php) | Tool has been viewed |

# Audit

Somewhat similar to logging but this functionality stores specific audit data for actions performed within the tool. The user
can choose to display their audit and it will show a comprehensive list of operations undertaken, plus errors and warnings generated.

This is implemented on the server/PHP side by a classes in the classes/audit directory. A class 'base.php' defines the basic functionality
and classes for each audit type extend this. Each class should define its own constructor to 'collect' the data that it requires pertinent
to that operation. The base class 'save' function writes the data to the databse.

Current audit classes are as follows...

| Audit              | Description
|--------------------|------------------------|
| [import_grades_users](../classes/audit/import_grades_users.php) | User has completed the import grades function |
| [nottoplevel](../classes/audit/notoplevel.php) | Tool has detected that no grade categories have been defined, indicating that the Gradebook has not been configured |
