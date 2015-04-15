@mod @mod_stampcoll
Feature: Student can give stamps to other students
  In order to provide feedback to my peer
  As a student
  I need to give them a stamp in the stamp collection module

  Scenario: Student gives a stamp to another student
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
    And the following "permission overrides" exist:
      | capability                    | permission  | role    | contextlevel    | reference   |
      | mod/stampcoll:viewotherstamps | Allow       | student | Activity module | stampcoll1  |
      | mod/stampcoll:givestamps      | Allow       | student | Activity module | stampcoll1  |
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test stamp collection"
    And I click on "Student 2" "link" in the "//table/tbody/tr[td='Student 2']/td[contains(concat(' ', normalize-space(@class), ' '), ' fullname ')]" "xpath_element"
    When I set the field "text" to "/> Well done! <strong>You</strong><img"
    And I click on "submit" "button" in the "region-main" "region"
    Then I should see "Student 2" in the "region-main" "region"
    And I should see "Number of collected stamps: 1" in the "region-main" "region"
    And "//div[@class='stamp-wrapper']/div[@class='stamp-image']/img" "xpath_element" should exist
    And "//div[@class='stamp-wrapper']/div[@class='stamp-text' and text()='/> Well done! <strong>You</strong><img']" "xpath_element" should exist
