# Description of database tables

## local_gugrades_scalevalue

In order to make the relationship between core Moodle scales "soft", the actual values of each scale value is configured on
the settings page for MyGrades. This table stores those definitions. The data is configured as a setting and therefore 
stored in the plugin's config tables, but a hook is run when these are changed to copy the data into this table. The format
is more useable (for performance reasons).

* **scaleid** - the Moodle scale id (mdl_scale table)
* **item** - the scale text for the scale item (e.g. 'A1:22')
* **value** - the integer value of the scale item

## local_gugrades_column

Table tracks columns in the grade capture table for the "selected" grade item displayed in MyGrades. This exists
mostly to support multiple "other" tables each with a different description. It also stores the "ordinary" columns
that are in use for that particular grade item. It means that when the grade item is selected, it is relatively
simple to grab the columns from this table to construct the table for presentation to the user. 

* **courseid** - course id, containing the grade category
* **gradeitemid** - the selected gradeitem (user selects from dropdown on Capture table)
* **gradetype** - what grade type the column represents. See classes/gradetype.php for the definitions. (e.g. FIRST, SECOND, OTHER)
* **other** - if OTHER is the selected gradetype, then this is the description of the field. Otherwise, blank.
* **points** - If true/1 then the column shows points. If false/0 the column shows scale values. 

## local_gugrades_columnbackup

A copy of `local_gugrades_column`. The grades backup stores `columnid`, which is the id from the columns table, so that id has to be recoverable as well. MyGrades writes a row here when a column is created, and updates it when the column's points flag changes. Upgrade copies the columns that already exist. The copy is kept when MyGrades is reset or a column is removed, and it is removed when the course is deleted. Restoring missing grades puts these columns back with their original ids before writing the grades.

* **columnid** - the `local_gugrades_column.id` this row was copied from
* **courseid** - containing course id
* **gradeitemid** - the Moodle grade item
* **gradetype** - FIRST, SECOND, OTHER and so on
* **other** - description when the grade type is OTHER, otherwise empty
* **points** - 1 for a points column, 0 for a scale column
* **timemodified** - when this copy was last written

## local_gugrades_grade

Contains the MyGrades individual grades. This is the cornerstone of the app and stores all versions of captured grades
and aggregated grade category items. 

* **courseid** - containing course id (for convenience)
* **gradeitemid** - the Moodle grade item that this record represents a value of
* **userid** - user id - whose grade is it
* **points** - If true/1 then the column shows points. If false/0 the column shows scale values.
* **rawgrade** - The "raw" grade value. If points, the point value. If scale the integer value of the scale.
* **convertedgrade** - I think this ended up being the same as the raw grade.
* **admingrade** - If the grade is an admingrade then this is the text code (NOT the displayed grade) of the admingrade. See classes/admingrades.php (e.g. GOODCAUSE_FO)
* **displaygrade** - the grade as displayed to the user. Whatever it happens to be
* **weightedgrade** - not used
* **gradetype** - what grade type the grade represents. See classes/gradetype.php for the definitions. (e.g. FIRST, SECOND, OTHER). Aggregated categories are a special case - CATEGORY
* **columnid** - maps to local_gugrades_column table. Not applicable for aggregated grades. 
* **iscurrent** - if true then stores current/active grade. If false, stores a historical / no longer used version. Used for history. 
* **iserror** - if true, grade is in some sort of error state (e.g. cannot aggregate because of mismatched grade types)
* **auditby** - userid of whoever made the action to write this grade. 
* **audittimecreated** - the timestamp of when the grade was written
* **auditcomment** - the optional comment supplied by the user when this grade was added/changed
* **dropped** - this grade has been dropped as part of the aggregation strategy
* **catoverride** - if true, this is a category that has been overridden by the user
* **normalisedweight** - the "normalised" weighting used for this item in aggregation.
* **capped** - course total award was capped because a resit was taken.

## local_gugrades_gradesbackup

A second copy of the current grade, holding the actual values only. MyGrades writes a row here whenever it saves a current grade. There is one row for each grade item, user and column. History, audit comments and aggregation weights stay in local_gugrades_grade.

After upgrade, existing current grades are copied by the scheduled task **Copy current MyGrades values to the backup table**. Each run copies grades for about 25 seconds. Cron repeats it every five minutes, and Run now on the Scheduled tasks page runs one chunk immediately. The upgrade itself only creates the table.

Rows whose displayed grade is a status message are left out. That includes No grade, Grades missing, Cannot aggregate, Not available, No data, and the child-category aggregation error. Administrative grades such as NS are kept.

The row is kept when grades are reset or removed from local_gugrades_grade, so the last values are still available if that table is damaged. It is removed when the course is deleted, and when personal data is deleted. **Restore grades** in MyGrades settings calls `grades::restore_missing_grade_backups($courseid)`, which writes back any current grades that are missing. Grades that are still present are left unchanged.

* **courseid** - containing course id
* **gradeitemid** - the Moodle grade item
* **userid** - whose grade it is
* **columnid** - the capture or category column this value belongs to. `local_gugrades_columnbackup` keeps that column, including this id
* **gradetype** - FIRST, SECOND, CATEGORY and so on
* **points** - 1 for a points grade, 0 for a scale grade
* **rawgrade** - point value, or scale index
* **convertedgrade** - grade used for ongoing aggregation
* **admingrade** - administrative grade code, when the grade is one
* **displaygrade** - grade as displayed
* **other** - description of an OTHER column, otherwise empty
* **iserror** - category grade is in an error state
* **notavailable** - category grade is not available
* **catoverride** - category total was overridden
* **capped** - course total award was capped because a resit was taken
* **gradeid** - id of the local_gugrades_grade row this value was copied from
* **timemodified** - when this copy was last written

## local_gugrades_audit

A number of actions result in an item being added to this table, purely for audit purposes. It's very much like the Moodle log but more specific. In many cases a core log
entry will have been made as well. 

* **courseid** - containing course id
* **userid** - the id of the user performing the action
* **relateduserid** - the id of the user who something was done TO (e.g. grade updated)
* **gradeitemid** - grade item id, if applicable.
* **timecreated** - timestamp of when record created
* **message** - text describing action

## local_gugrades_config

Configuration settings for MyGrades. Only used for the course on/off selection at the moment.

* **courseid** - id of containing course
* **gradeitemid** - IF setting related to grade item
* **name** - name of config item
* **value** - value of config item

## local_gugrades_scaletype

Defines what sort of scale the core Moodle scale is. Currently only supported options are 'schedulea' and 'scheduleb'

* **scaleid** - the Moodle scale id (mdl_scale table)
* **type** - the type. Must contain either 'schedulea' or 'scheduleb'

## local_gugrades_map

Defines a conversion map

* **courseid** - id of containing course
* **name** - name of conversion map as specified by user
* **scale** - currently must be 'schedulea' or 'scheduleb'
* **maxgrade** - The maximum grade value
* **userid** - user who created this map
* **timecreated** - the timestamp of when the map was created
* **timemodified** - the timestamp of when the map was changed.

## local_gugrades_map_item

Associates a converted grade item or category with the appropriate conversion map. This is populated when the user makes the conversion.

* **courseid** - id of containing course
* **mapid** - which map - link to local_gugrades_map
* **gradeitemid** - the grade item that is being converted
* **gradecategoryid** - the grade category that is being converted (if that is the case)
* **userid** - id of user making conversion
* **timemodified** - when the conversion was added/changed

## local_gugrades_map_value

The value of each conversion map item

* **mapid** - link to local_gugrades_map
* **percentage** - the percentage border value if this entry in the conversion table
* **scalevalue** - the integer value of the scale into which it maps
* **band** - the scale item (A1, A2 etc)

## local_gugrades_hidden

Stores grade items which have been hidden in MyGrades

* **courseid** - id of containing course
* **gradeitemid** - id of hidden grade item
* **userid** - who hid the item

## local_gugrades_resitrequired

Stores users for whome a resit has been selected

* **courseid** - id of containing course
* **userid** - user id

## local_gugrades_agg_conversion

Table is not used.

## local_gugrades_altered_weight

When weights are altered for an individual user in MyGrades, the revised weights are stored here. There will be an 
entry for each grade item within a grade category (for that user)

* **courseid** - id of containing course
* **categoryid** - the category id of the parent cagegory for these weights
* **gradeitemid** - the grade item id for which a changed weight is defined. 
* **userid** - the user who this applies to.
* **weight** - revised weight as a decimal
* **timealtered** - timestamp of when it was changed.

