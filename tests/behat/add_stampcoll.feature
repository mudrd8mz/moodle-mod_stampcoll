@mod @mod_stampcoll
Feature: Add stamp collection activity
  In order to let students collect stamp marks
  As a teacher
  I need to add stamp collection activity module to a course

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
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on

  Scenario: Add and view stamp collection with displaying all users off
    And I add a "Stamp collection" to section "1" and I fill the form with:
      | Name                            | Test stamp collection       |
      | Description                     | This is a test collection.  |
      | Display users with no stamps    | No                          |
    When I follow "Test stamp collection"
    Then I should see "No stamps in this collection"

  Scenario: Add and view stamp collection with displaying all users on
    And I add a "Stamp collection" to section "1" and I fill the form with:
      | Name                            | Test stamp collection       |
      | Description                     | This is a test collection.  |
      | Display users with no stamps    | Yes                         |
    When I follow "Test stamp collection"
    Then I should see "0" in the "//table/tbody/tr[td='Student 1']/td[contains(concat(' ', normalize-space(@class), ' '), ' count ')]" "xpath_element"
    And I should see "0" in the "//table/tbody/tr[td='Student 2']/td[contains(concat(' ', normalize-space(@class), ' '), ' count ')]" "xpath_element"
    And I should see "0" in the "//table/tbody/tr[td='Student 3']/td[contains(concat(' ', normalize-space(@class), ' '), ' count ')]" "xpath_element"
