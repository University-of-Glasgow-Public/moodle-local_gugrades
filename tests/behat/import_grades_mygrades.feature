@local @local_gugrades @javascript
  Feature: Testing import_grades_mygrades in local_gugrades
    In order to view staff Mygrades
    I need to be logged in
  Background:
    Given the following "custom field categories" exist:
        | name              | component   | area   | itemid |
        | Student MyGrades  | core_course | course | 0      |
    And the following "courses" exist:
        | fullname | shortname | format | Course start date     | id |
        | Course 1 | C1        | topics | 16 April 2025 00 00   | 2 |
    And the following "users" exist:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@example.com |
        | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
        | user     | course | role |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student |
    And I log in as "admin"
    And I set the following administration settings values:
        | grade_aggregations_visible | Weighted mean of grades,Mean of grades,Weighted mean of grades,Simple weighted mean of grades,Mean of grades (with extra credits),Median of grades,Lowest grade,Highest grade,Mode of grades,Natural |
    And I am on the "local_gugrades > Settings" page
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I choose the "Add category" item in the "Add" action menu
    And I set the following fields to these values:
        | Category name | Summative |
        | Aggregation   | Weighted mean of grades |
    And I click on "Save" "button" in the "New category" "dialogue"
    And the following "activities" exist:
        | activity | name              | course | idnumber | assignsubmission_onlinetext_enabled      | gradecategory |
        | assign   | Test assignment 1 | C1     | assign1  | 1                                        | Summative     |
        | assign   | Test assignment 2 | C1     | assign2  | 1                                        | Summative     |
    And I am on the "Test assignment 1" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "grade[modgrade_type]" to "Scale"
    And I set the field "grade[modgrade_scale]" to "Schedule A"
    And I press "Save and display"
    And I go to "Student 1" "Test assignment 1" activity advanced grading page
    And I set the field "Grade" to "A4"
    And I press "Save changes"
  Scenario: Import Grades
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "MyGrades (Beta)" in current page administration
    And I click on "Test assignment 1" "link" in the "captureselect" "region"
    And I click on "Import grades" "button"
    And I click on "Import grades" "button" in the "vm-46-content" "region"
    And I click on "Import grades" "button" in the "vm-46-content" "region"
    Then I should see "A4"
