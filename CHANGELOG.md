# Changelog

### 1.2.0

Change: A better all round solution to 1.1.16's performance issue resulting in no query execution just
        statement preparation.
Change: Potentially breaking change - removed a spurious firing of collectionUpdatedEvent that caused
        doubling of query execution time in some cases. No observed side effects from this removal.

### 1.1.16

Change: Additional columns are no longer assessed if using unSearchedHtml to improve performance.
        This is not a great solution - ideally additional columns would not require a DB query to run
        in order to be evaluated.

### 1.1.15

Fixed:  Fix for 1.1.14... 

### 1.1.14

Fixed:  Better support for not causing DB load if using unsearched html

### 1.1.13

Fixed:  Issue with broken sorting if columns are not initialize

### 1.1.12

Fixed:	Issue with double pagers not always triggering via XHR

### 1.1.11

Fixed:	Issue with temp path being created with wrong permissions

### 1.1.10

Fixed:  Able to prevent columns from being sorted 

### 1.1.9

Fixed:	On export of data from table, decode any special characters.

### 1.1.8

Fixed:	Issue with temp path

### 1.1.7

Fixed:	Issue with exportList() not creating the temp folder.

### 1.1.6

Fixed:	Issue with pager showing when unSearchedHtml in play

### 1.1.5

Fixed:  Table columns weren't applying sorting classes correctly.

### 1.1.4

Fixed:  Fixed tables export to use TEMP_DIR"

### 1.1.3

Added:  Support for composite columns being used in tables
Fixed:  Export now exports without ranging

### 1.1.2

Fixed:	Fixed issue where counts made using the getCollectionEvent filter were confused due to combining filters

### 1.1.1

Added:  TableView now has overridable methods to control the behaviour of printing cells and headers.

### 1.1.0

Added:  Storing pager and sort column in URL state

### 1.0.4

Added:  Table now consults additionalColumns of a Collection to support sorting and mapping for aggregates and pull ups.

### 1.0.3

Added:  Ability to set row css class event

### 1.0.2

Fixed:  Fixing issue where the collection and pagesize was not being set before the view had been initialised

### 1.0.1

Added:  Setters for NoDataHtml, RepeatPagerAtBottom and Unsearched HTML

### 1.0.0

Added:      Changelog
