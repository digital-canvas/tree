# Tree

This library is meant to help with hierarchal data.

## Setting field structure

The keys used for node metatata are configurable to match or avoid conflicts with your existing data structure.

Structure can be passed to the constructor as an array.

```
<?php
use DigitalCanvas\Tree;

$tree = new Tree([
    // The unique identifier for the node. Required for all nodes
    'id' => 'id', 
    // The id of the parent node. Required for all nodes.
    'parent' => 'parent_id', 
    
    // The left field for nested set pattern.
    'left' => 'left', 
    // The right field for nested set pattern
    'right' => 'right', 
    
    // The nodes will be resorted within parents by this field.  Set to the left column if data is stored as nested set. Defaults to id column
    'sort' => 'id', 
    
    // When converted to nested array this will store the child nodes.
    'children' => 'children',
    // The nesting level of the node
    'level' => 'level',
    
    // Used for menus. The current node and all parents will be marked as selected.  Can be used to set an active class on menus.
    'selected' => 'selected',
    // Used for menus. Can be used to only show root nodes, siblings, and direct children of the current node.
    'visible' => 'visible',
    // Array of breadcroumbs for each node.
    'breadcrumbs' => 'breadcrumbs',
    // Boolean value set to true if the node is a first child
    'first' => 'first',
    // Boolean value set to true if the node is a last child 
    'last' => 'last',
    // Boolean value set to true is the node is an only child
    'only' => 'only',
    // Array of icons for each node.  This can be used to render hierarchal lines for nodes.
    'icons' => 'icons',
    // The default icon sets. 
    'icon_bar' => "│\xC2\xA0",
    'icon_empty' => "\xC2\xA0\xC2\xA0",
    'icon_join' => '├─',
    'icon_bottom' => '└─',
]);
```

## Loading Data

### Loading from adjacency list array

Assuming a database table with id for primary key and parent_id as foreign key to parent node.  Root nodes should have null for parent_id.

```
<?php
use DigitalCanvas\Tree;

// Fetch all items from database
$items = $db->fetchAll("SELECT * FROM table");

$tree = new Tree();
$tree->fromFlatArray($items);
var_dump($tree->getData());
```

### Loading from a nested array

You can also load data from an array with nested children.

```
<?php
use DigitalCanvas\Tree;

$items = [
    [
        'id' => 'A',
        'parent_id' => null,
        'children' => [
            [
                'id' => 'B',
                'parent_id' => 'A',
                'children' => []
            ]
        ]
    ], 
    [
        'id' => 'B',
        'parent_id' => null,
        'children' => []
    ]
];

$tree = new Tree();
$tree->fromRecursiveArray($items);
var_dump($tree->getData());
```

## Getting Data

Data can be returned as a flat or nested array.

### Returning data as a flat array

This is the default structure returned.

```
// Returns a flat array
$tree->getData()

// Also returns a flat array
$tree->getArray();
```

### Returning data as a nested array

You can convert the data to a nested array by passing false as a second parameter to the `getArray` method.

```
// Returns a nested array
$tree->getArray(null, false);
```
