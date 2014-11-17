@mod @mod_stampcoll
Feature: Teacher can add, update and delete stamps
  In order to manage stamps in the whole collection
  As a teacher
  I need to be able to add, update and delete stamps

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname  | email                |
      | teacher1    | Teacher   | 1         | teacher1@example.com |
      | student1    | Student   | 1         | student1@example.com |
      | student2    | Student   | 2         | student2@example.com |
      | student3    | Student   | 3         | student3@example.com |
    And the following "courses" exist:
      | fullname    | shortname | category  |
      | Course 1    | C1        | 0         |
    And the following "course enrolments" exist:
      | user        | course    | role              |
      | teacher1    | C1        | editingteacher    |
      | student1    | C1        | student           |
      | student2    | C1        | student           |
      | student3    | C1        | student           |
    And the following "activities" exist:
      | activity  | course  | idnumber    | name                    | intro                               | displayzero |
      | stampcoll | C1      | stampcoll1  | Test stamp collection   | Test stamp collection description   | 1           |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test stamp collection"
    And I follow "Manage stamps"

  Scenario: Teacher adds stamps to multiple students
    When I set the following fields to these values:
      | Give new stamp to Student 2 | New stamp for s2    |
      | Give new stamp to Student 3 | New stamp for s3    |
    And I click on "Update stamps" "button"
    And I set the field "Give new stamp to Student 2" to "Yet another stamp for s2"
    And I click on "Update stamps" "button"
    Then the field "Update stamp #1 of Student 2" matches value "New stamp for s2"
    And the field "Update stamp #2 of Student 2" matches value "Yet another stamp for s2"
    And the field "Update stamp #1 of Student 3" matches value "New stamp for s3"

  Scenario: Teacher updates the text associated with a stamp
    When I set the following fields to these values:
      | Give new stamp to Student 1 | New stamp for s1    |
      | Give new stamp to Student 2 | New stamp for s2    |
    And I click on "Update stamps" "button"
    And I set the field "Update stamp #1 of Student 2" to "Fixed stamp for s2"
    And I click on "Update stamps" "button"
    Then the field "Update stamp #1 of Student 1" matches value "New stamp for s1"
    And the field "Update stamp #1 of Student 2" matches value "Fixed stamp for s2"

  Scenario: Teacher deletes a given stamp
    Given I set the field "Give new stamp to Student 2" to "Temporary stamp for s2"
    And I click on "Update stamps" "button"
    And "//*[label='Update stamp #1 of Student 2']" "xpath_element" should exist
    When I click on "Delete" "link" in the "region-main" "region"
    And I click on "Continue" "button"
    Then "//*[label='Update stamp #1 of Student 2']" "xpath_element" should not exist
