# Employee Lookup Spec

## Requirement: Admin can import employee rows from Excel

The system SHALL support importing employee rows from Excel into a dedicated employee record type.

### Scenario: Import employee file with dynamic headers

Given an admin uploads an employee Excel file for type `nhan-vien`
When the system reads the worksheet
Then the system shall detect stable fields from header aliases
And the system shall store all recognized row values for that employee
And the system shall persist the full detail row in structured JSON for later display.

### Scenario: CCCD is missing in the uploaded file

Given an imported employee row has no CCCD column value
When the row is saved
Then the system shall still create the employee record
And the system shall allow admin to add CCCD later from the admin edit view.

## Requirement: Public users can look up employee details by CCCD

The system SHALL let a public user retrieve one employee detail record by entering CCCD.

### Scenario: Matching CCCD exists

Given a public user enters a CCCD that matches an employee record
When the lookup request succeeds
Then the system shall return the employee's normalized fields
And the system shall show all imported detail fields stored for that employee.

### Scenario: CCCD does not match any employee

Given a public user enters a CCCD that does not match any employee record
When the lookup request succeeds
Then the system shall return a clear not-found message.

## Requirement: Public users can replace the stored CCCD

The system SHALL support replacing the employee record's current CCCD with a new CCCD.

### Scenario: New CCCD is valid and unique

Given a public user has successfully opened an employee detail record
When the user submits a new CCCD that passes validation
And no other employee record already uses that CCCD
Then the system shall update the employee record's stored CCCD
And future lookup shall succeed with the new CCCD.

### Scenario: New CCCD is already used

Given another employee already has the submitted new CCCD
When the user attempts to save the change
Then the system shall reject the update
And the system shall return a duplicate-CCCD error message.

### Scenario: Update requires secondary confirmation

Given the deployment enables a secondary confirmation rule
When the user submits a CCCD change without the required confirmer
Then the system shall reject the update
And the system shall explain which additional field is required.
