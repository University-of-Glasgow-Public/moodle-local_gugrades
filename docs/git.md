Vue has a build step that generates files in the ui/dist folder. This creates a problem because devs working
on the Vue code at the same time can cause conflicts in these built assets. There is no need to resolve
these conflicts as they can just be rebuilt - and if you've left the 'watch' process running they will be
as soon as the view source changes. In order to prevent these conflicts an 'ours' strategy is imposed.

== .gitattributes ==

This project file contains the line, 

ui/dist/** merge=ours

...which enables the 'ours' merge strategy for the ui/dist folder and contents. 

However, in order to make this work the ours strategy should be defined as follows...

git config --get merge.ours.driver

All devs need to run this command once. 