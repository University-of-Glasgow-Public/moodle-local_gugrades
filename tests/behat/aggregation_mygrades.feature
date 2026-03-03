@local @local_gugrades @gugradesagg @javascript
Feature: Testing aggregation_mygrades in local_gugrades
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
        | assign   | Test assignment 3 | C1     | assign3  | 1                                        | Summative     |
    And I am on the "Test assignment 1" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "grade[modgrade_type]" to "Scale"
    And I set the field "grade[modgrade_scale]" to "Schedule A"
    And I press "Save and display"
    And I go to "Student 1" "Test assignment 1" activity advanced grading page
    And I set the field "Grade" to "B1"
    And I press "Save changes"
    And I am on the "Test assignment 2" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "grade[modgrade_type]" to "Scale"
    And I set the field "grade[modgrade_scale]" to "Schedule A"
    And I press "Save and display"
    And I go to "Student 1" "Test assignment 2" activity advanced grading page
    And I set the field "Grade" to "C1"
    And I press "Save changes"
    And I am on the "Test assignment 3" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "grade[modgrade_type]" to "Scale"
    And I set the field "grade[modgrade_scale]" to "Schedule A"
    And I press "Save and display"
    And I go to "Student 1" "Test assignment 3" activity advanced grading page
    And I set the field "Grade" to "B3"
    And I press "Save changes"
  Scenario: Importing Grades and checking aggregation
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "MyGrades (Beta)" in current page administration
    And I click on "Test assignment 1" "link" in the "captureselect" "region"
    And I click on "Import grades" "button"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    Then I should see "B1"
    And I click on "Course grade aggregation" "button"
    Then I should see "B1"
    And I should see "33.333%"
    And I should see "Grades missing" in the total cell
    And I click on "Assessment grade capture" "button"
    And I click on "Test assignment 2" "link" in the "captureselect" "region"
    And I click on "Import grades" "button"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    Then I should see "C1"
    And I click on "Course grade aggregation" "button"
    Then I should see "C1"
    And I should see "66.667%"
    And I should see "Grades missing" in the total cell
    And I click on "Assessment grade capture" "button"
    And I click on "Test assignment 3" "link" in the "captureselect" "region"
    And I click on "Import grades" "button"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    Then I should see "B3"
    And I click on "Course grade aggregation" "button"
    Then I should see "B3"
    And I should see "100%"
    Then I should see "B3 (15.33333)" in the total cell
  Scenario: IS Overrides Total
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "MyGrades (Beta)" in current page administration
    And I click on "Test assignment 1" "link" in the "captureselect" "region"
    And I click on "Import grades" "button"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    And I click on "Import grades" "button" in the ".vm-content" "css_element"
    Then I should see "B1"
    And I click on "Course grade aggregation" "button"
    Then I should see "B1"
    And I should see "33.333%"
    And I should see "Grades missing" in the total cell
    And I click on "Assessment grade capture" "button"
    And I click on "Test assignment 2" "link" in the "captureselect" "region"
    And I click on "Action" "button"
    And I click on "Add grade" "link"
    And I wait 3 seconds
    And I set the following fields to these values:
        | Reason for additional grade | 2nd grade                    |
        | Admin grade                 | IS - Interruption of Studies |
    And I wait 3 seconds
    And I click on "Submit" "button" in the ".vm-content" "css_element"
    And I click on "Course grade aggregation" "button"
    Then I should see "IS" in the total cell
