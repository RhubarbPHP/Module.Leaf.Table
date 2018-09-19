Table
=====

Drawing a table may seem like a trivial task however in a normal browser application a table needs
to handle large lists smartly.

At its most basic the `Table` component receives a Stem Collection and an array of column definitions.
When rendered it will generate an HTML table to present the data in the collection with this
configuration. However in addition it provides pagination, column sorting and a range of events
to allow XHR interaction with events on individual rows. Tables can also be summarised through
footers.

## Basic Usage

Create a `Table` control, with a collection and set the columns structure:

``` demo[examples/BasicUsage/StringColumns.php,StringColumnsView.php]
```

In this example our column definitions are just strings but because they match columns defined in
our model's schema, the Table class upscales these to `ModelColumn` objects. In fact some columns
may map to more specific specification types based on their column type. In this example the
Boolean "Sent" column is being upscaled to a `BooleanColumn`. Time and date are also treated in this way.

When columns are upscaled in this way the label will interpret camelCaseColumnNames and convert them
to space separated title cased strings.

### Labels and templates

Each column has a label used in the header row. This can be changed by supplying a string key
for your column definition:

``` demo[examples/BasicUsage/LabelMapping.php,LabelMappingView.php]
```

Note how the Status column has been changed to a string including placeholders using braces. As
this string no longer matches a model column name it will be upscaled to a `TemplateColumn`. A
`TemplateColumn` will replace all matching placeholders that exist in the model with their
appropriate values. Implemented in this way however you loose the ability to sort on this column
as the Table no longer can guess which column should be sorted on.

## Being specific with column types.

Instead of passing strings in the columns array you can pass `TableColumn` objects. These
encapsulate the full specification of a column and so give you full control. You can of 
course create your own column objects to provide very specific and custom behaviours.

Built into the Table module are the following Column types:

TableColumn
:   The base column type. Abstract so it must be extended. It provides a pattern of two main
    functions for returning and formatting values discussed in more detail below.

Template
:   Passed a template string with {} placeholders, it will replace those placeholders that match
    columns on the model.

SortableColumn
:   Not actually a class but an interface which if implemented on a column provides the Table
    with the name of the column to sort by when the column is clicked.

ModelColumn
:   Implements SortableColumn and takes a column name as a constructor argument. Will present
    that value in the model. Registers the same column as the column to sort by.
    
BooleanColumn
:   Extends ModelColumn and formats boolean values into "Yes" or "No"

TimeColumn
:   Formats model values using a supplied PHP date format. Defaults to H:i

DateColumn
:   Formats model values using a supplied PHP date format. Defaults to jS F Y

ClosureColumn
:   Takes a closure function in the constructor and calls this passing in the model for every
    row. The return value of the function is printed as the cell value.
    
LeafColumn
:   Takes a reference to a Leaf object (usually a control) and prints it using a view index
    of the unique identifier of the model for the row. Discussed in more detail below. 

``` demo[examples/BasicUsage/MixedColumns.php,MixedColumnsView.php]
```

Having a reference to the actual column definition lets you also configure custom CSS classes
for the &lt;td&gt; and &lt;th&gt; tags.

``` demo[examples/BasicUsage/CellFormatting.php,CellFormattingView.php]
```

## Handling related values

## Building a custom column definition

## Changing the sort behaviour

## Adding interactive elements