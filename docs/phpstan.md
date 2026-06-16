* composer require --dev phpstan/phpstan
 
 
* composer require --dev micaherne/phpstan-moodle
 
create a phpstan.neon file (in the root of Moodle) with something like....
 
 

    includes:
        - vendor/micaherne/phpstan-moodle/extension.neon
    parameters:
        moodle:
            rootDirectory: /app/html
 


 
Then run 
 
* vendor/bin/phpstan analyze --level 3 local/gugrades/classes/
 
level starts at 0 for nothing too worrying. The bigger you make the number, the worse it gets