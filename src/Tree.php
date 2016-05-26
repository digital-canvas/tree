<?php
namespace DigitalCanvas\Tree;

use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;

/**
 * Class Tree
 *
 * @package BibleSource
 */
class Tree implements IteratorAggregate, Countable
{

    /**
     * Data goes in this array
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Stores flat data.
     * Only for temporary storage, is emptied as soon as tree is parsed.
     *
     * @var array
     */
    protected $_temp;

    /**
     * The key of the node id
     *
     * @var string
     */
    protected $_node_id = 'id';

    /**
     * The key of the id of the node parent
     *
     * @var string
     */
    protected $_node_parent = 'parent_id';

    /**
     * The key of the nested set left value
     *
     * @var string
     */
    protected $_node_left = 'left';

    /**
     * The key of the nested set right value
     *
     * @var string
     */
    protected $_node_right = 'right';

    /**
     * The key to sort nodes by. Defaults to is column
     *
     * @var string
     */
    protected $_node_sort;

    /**
     * The key to use to store children
     *
     * @var string
     */
    protected $_node_children = 'children';

    /**
     * The key to use to store the level
     *
     * @var string
     */
    protected $_node_level = 'level';

    /**
     * The key to use to store the breadcrumbs for each node
     *
     * @var string
     */
    protected $_node_breadcrumbs = 'breadcrumbs';

    /**
     * The key to use to store the icons for each node
     *
     * @var string
     */
    protected $_node_icons = 'icons';

    /**
     * Icon for empty space
     *
     * @var string
     */
    protected $_icon_empty = "\xC2\xA0\xC2\xA0";

    /**
     * Icon for child with siblings
     *
     * @var string
     */
    protected $_icon_join = '├─';

    /**
     * Icon for last child leaf
     *
     * @var string
     */
    protected $_icon_bottom = '└─';

    /**
     * First icon for child
     *
     * @var string
     */
    protected $_icon_bar = "│\xC2\xA0";

    /**
     * The key to use to store flag for first child nodes
     *
     * @var string
     */
    protected $_node_first = 'first';

    /**
     * The key to use to store flag for last child nodes
     *
     * @var string
     */
    protected $_node_last = 'last';

    /**
     * The key to use to store flag for only child nodes
     *
     * @var string
     */
    protected $_node_only = 'only';

    /**
     * The key to use to mark if the node is selected
     *
     * @var string
     */
    protected $_node_selected = 'selected';

    /**
     * The key to use to mark if the node is visible
     *
     * @var string
     */
    protected $_node_visible = 'visible';

    /**
     * Class constructor
     *
     * @param array $structure
     */
    public function __construct(array $structure = [])
    {
        // Set default structure
        $this->setStructure($structure);
    }

    /**
     * Sets the columns to use as special columns
     *
     * @param array $structure
     *
     * @return void
     */
    public function setStructure(array $structure = [])
    {
        if (array_key_exists('id', $structure)) {
            $this->_node_id = $structure['id'];
        }
        if (array_key_exists('parent', $structure)) {
            $this->_node_parent = $structure['parent'];
        }
        if (array_key_exists('left', $structure)) {
            $this->_node_left = $structure['left'];
        }
        if (array_key_exists('right', $structure)) {
            $this->_node_right = $structure['right'];
        }
        if (array_key_exists('sort', $structure)) {
            $this->_node_sort = $structure['sort'];
        } elseif (is_null($this->_node_sort)) {
            $this->_node_sort = $this->_node_id;
        }
        if (array_key_exists('children', $structure)) {
            $this->_node_children = $structure['children'];
        }
        if (array_key_exists('level', $structure)) {
            $this->_node_level = $structure['level'];
        }
        if (array_key_exists('selected', $structure)) {
            $this->_node_selected = $structure['selected'];
        }
        if (array_key_exists('visible', $structure)) {
            $this->_node_visible = $structure['visible'];
        }
        if (array_key_exists('breadcrumbs', $structure)) {
            $this->_node_breadcrumbs = $structure['breadcrumbs'];
        }
        if (array_key_exists('first', $structure)) {
            $this->_node_first = $structure['first'];
        }
        if (array_key_exists('last', $structure)) {
            $this->_node_last = $structure['last'];
        }
        if (array_key_exists('only', $structure)) {
            $this->_node_only = $structure['only'];
        }
        if (array_key_exists('icons', $structure)) {
            $this->_node_icons = $structure['icons'];
        }
        if (array_key_exists('icon_bar', $structure)) {
            $this->_icon_bar = $structure['icon_bar'];
        }
        if (array_key_exists('icon_empty', $structure)) {
            $this->_icon_empty = $structure['icon_empty'];
        }
        if (array_key_exists('icon_join', $structure)) {
            $this->_icon_join = $structure['icon_join'];
        }
        if (array_key_exists('icon_bottom', $structure)) {
            $this->_icon_bottom = $structure['icon_bottom'];
        }
    }

    /**
     * Parses flat array into tree data
     *
     * @param array $data
     *
     * @return void
     */
    public function fromFlatArray(array $data)
    {
        // Clear Data
        $this->clearData();
        // Sort the array by parent
        uasort($data, [$this, 'nodeSort']);
        // Init arrays
        $this->_temp = array_values($data);
        // Parse tree
        $this->addNodes();
        // Generate Breadcrumbs
        $this->generateBreadcrumbs();
        // Temporary flat array no longer needed.
        $this->_temp = null;
    }

    /**
     * Returns data as an array
     *
     * @param string|array $selected [optional] These nodes will appear as selected.
     * @param bool         $flat     [optional] If false data will be a multi-level array
     * @param int          $start    Start from this node
     * @param int          $limit    If not null only up to this level will be returned.  Use 0 to get only root nodes.
     *
     * @return array
     */
    public function getArray($selected = null, $flat = true, $start = null, $limit = null)
    {
        if ($flat) {
            unset($flat);
            $temp = $this->_data;
            if (!is_null($start) || !is_null($limit) || !is_null($selected)) {
                foreach ($temp as $key => $node) {
                    if (!is_null($limit) && $node[$this->_node_level] > $limit) {
                        unset($temp[$key]);
                    } elseif (!is_null($start) && !in_array($start, $node[$this->_node_breadcrumbs])) {
                        unset($temp[$key]);
                    } elseif (!is_null($selected)) {
                        if (is_array($selected)) {
                            $node_selected = false;
                            $visible = false;
                            foreach ($selected as $index) {
                                if ($this->checkSelected($key, $index)) {
                                    $node_selected = true;
                                }
                                if ($this->checkVisible($key, $index, $start)) {
                                    $visible = true;
                                }
                            }
                            $temp[$key][$this->_node_selected] = $node_selected;
                            $temp[$key][$this->_node_visible] = $visible;
                            unset($node_selected);
                        } else {
                            $temp[$key][$this->_node_selected] = $this->checkSelected($key, $selected);
                            $temp[$key][$this->_node_visible] = $this->checkVisible($key, $selected, $start);
                        }
                    } else {
                        $temp[$key][$this->_node_selected] = false;
                        $temp[$key][$this->_node_visible] = $this->checkVisible($key, null, $start);
                    }
                    unset($key, $node);
                }
            }

            return $temp;
        }

        return $this->makeMultiLevel($selected, $start, $limit);
    }

    /**
     * Parses multi-level array into tree data
     *
     * @param array $data
     *
     * @return void
     */
    public function fromRecursiveArray(array $data)
    {
        // Clear Data
        $this->clearData();
        // Create tree
        $this->convertToFlat($data);
        $this->addMetaData();
        // Generate Breadcrumbs
        $this->generateBreadcrumbs();
        // temp array is no longer needed
        $this->_temp = null;
    }

    /**
     * Converts a recursive array into a flat one
     *
     * @param array  $data
     * @param string $parent [optional]
     * @param int    $level  [optional]
     *
     * @return void
     */
    protected function convertToFlat($data, $parent = null, $level = 0)
    {
        $orderby = 0;
        foreach ($data as $key => $node) {
            unset($data[$key]);
            $item = $node;
            // clear out data to unsure structure
            unset($item[$this->_node_id], $item[$this->_node_parent], $item[$this->_node_sort], $item[$this->_node_children], $item[$this->_node_level], $item[$this->_node_breadcrumbs]);
            $item[$this->_node_id] = $node[$this->_node_id];
            $item[$this->_node_parent] = $parent;
            if ($this->_node_sort) {
                $item[$this->_node_sort] = $orderby;
            }

            $item[$this->_node_level] = $level;
            // save current node
            $this->_data[$item[$this->_node_id]] = $item;
            unset($item);
            // add child nodes
            if (isset($node[$this->_node_children]) && is_array($node[$this->_node_children])) {
                $id = $node[$this->_node_id];
                $children = $node[$this->_node_children];
                unset($node);
                $this->convertToFlat($children, $id, $level + 1);
                unset($id, $children);
            }
            unset($node);
            $orderby++;
        }
        unset($orderby, $level, $parent);
    }

    /**
     * Returns an array leading back to the root
     *
     * @param string $id
     *
     * @return array
     */
    public function getBreadcrumb($id)
    {
        if (!array_key_exists($id, $this->_data)) {
            throw new Exception('Requested node does not exist.');
        }

        return $this->_data[$id][$this->_node_breadcrumbs];
    }

    /**
     * Checks if node is selected
     *
     * @param string $id
     * @param string $selected
     *
     * @return bool
     */
    public function checkSelected($id, $selected)
    {
        if (array_key_exists($selected, $this->_data)) {
            return in_array($id, $this->_data[$selected][$this->_node_breadcrumbs]);
        } else {
            return false;
        }
    }

    /**
     * Checks if node is visible
     *
     * @param string $id
     * @param string $selected
     * @param string $start
     *
     * @return bool
     */
    public function checkVisible($id, $selected = null, $start = null)
    {
        if (is_null($start) || !array_key_exists($start, $this->_data)) {
            if ($this->_data[$id][$this->_node_level] == 0) {
                // If starting with root nodes always show top levels
                return true;
            }
        } else {
            if ($this->_data[$id][$this->_node_level] == $this->_data[$start][$this->_node_level]) {
                // Show siblings of root nodes
                return true;
            }
        }
        if ($selected) {
            $crumbs = $this->_data[$id][$this->_node_breadcrumbs];
            array_pop($crumbs);
            $parent = array_pop($crumbs);
            if (in_array($parent, $this->_data[$selected][$this->_node_breadcrumbs])) {
                // If the parent of the current node is in the breadcrumbs of the selected node
                return true;
            }
        }

        // Otherwise return false
        return false;
    }

    /**
     * Generates breadcrumbs
     *
     * @return void
     */
    protected function generateBreadcrumbs()
    {
        foreach ($this->_data as $node) {
            $crumbs = ($node[$this->_node_id] > 0) ? [$node[$this->_node_id]] : [];
            // add direct parent
            $parent = $node[$this->_node_parent];
            while ($parent) {
                $crumbs[] = $parent;
                $parent = $this->_data[$parent][$this->_node_parent];
            }
            $this->_data[$node[$this->_node_id]][$this->_node_breadcrumbs] = array_reverse($crumbs);
            unset($crumbs, $node, $parent);
        }
    }

    /**
     * Returns data array for caching
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Sets data array
     * Used to load from cache
     * Can break class if structure was not generated by this class
     *
     * @param array $data
     *
     * @return void
     */
    public function setData(array $data)
    {
        // Clear Data
        $this->clearData();
        // Load cached data
        $this->_data = $data;
    }

    /**
     * Generates tree from data
     *
     * @param string $parent [optional]
     * @param int    $level  [optional]
     *
     * @return void
     */
    protected function addNodes($parent = null, $level = 0)
    {
        // Loop through all items
        $keys = array_column($this->_temp, $this->_node_id);
        foreach ($this->_temp as $item) {
            $key = array_search($item[$this->_node_id], $keys);
            $prev = ($key > 0) ? $this->_temp[$key - 1] : null;
            $next = ($key < count($keys) - 1) ? $this->_temp[$key + 1] : null;
            $item[$this->_node_first] = false;
            $item[$this->_node_last] = false;
            $item[$this->_node_only] = false;
            if ($prev) {
                if ($prev[$this->_node_parent] != $item[$this->_node_parent]) {
                    $item[$this->_node_first] = true;
                }
            } else {
                $item[$this->_node_first] = true;
            }
            if ($next) {
                if ($next[$this->_node_parent] != $item[$this->_node_parent]) {
                    $item[$this->_node_last] = true;
                }
            } else {
                $item[$this->_node_last] = true;
            }
            if ($item[$this->_node_first] && $item[$this->_node_last]) {
                $item[$this->_node_only] = true;
            }
            $item[$this->_node_icons] = [];
            if ($level > 0) {
                if ($level > 1) {
                    if ($this->_data[$parent][$this->_node_last]) {

                        $item[$this->_node_icons][] = $this->_icon_empty;
                    } else {
                        $item[$this->_node_icons][] = $this->_icon_bar;
                    }


                    $item[$this->_node_icons] += array_fill(0, $level - 1, $this->_icon_bar);
                }

                if ($item[$this->_node_only]) {
                    $item[$this->_node_icons][] = $this->_icon_bottom;
                } elseif ($item[$this->_node_last]) {
                    $item[$this->_node_icons][] = $this->_icon_bottom;
                } else {
                    $item[$this->_node_icons][] = $this->_icon_join;
                }
            }
            if ($item[$this->_node_parent] == $parent) {
                // The item has the same parent as previous so it has the same level
                $item[$this->_node_level] = $level;
                $item[$this->_node_breadcrumbs] = [];
                // Add item to data array
                $this->_data[$item[$this->_node_id]] = $item;
                if ($item[$this->_node_id] != $parent) {
                    // The item is not the previous parent so recurse
                    $this->addNodes($item[$this->_node_id], $level + 1);
                }
            }
        }
    }

    /**
     * Converts flat array into multi-level array
     *
     * @param null   $selected
     * @param string $start       Passing a node id will only return children of that node.
     * @param int    $level_limit If not null only up to this level will be returned.  Use 0 to get only root nodes.
     *
     * @return array
     */
    protected function makeMultiLevel($selected = null, $start = null, $level_limit = null)
    {
        // Trees mapped
        $trees = [];
        $l = 0;
        if (count($this->_data) > 0) {
            // Node Stack. Used to help building the hierarchy
            $stack = [];

            foreach ($this->_data as $item) {
                if (!is_null($start) && !in_array($start, $item[$this->_node_breadcrumbs])) {
                    // This node is not on the requested branch
                    continue;
                }
                if (!is_null($level_limit) && $level_limit > $item[$this->_node_level]) {
                    // This node is outside of limits
                    continue;
                }
                if (!is_null($selected)) {
                    if (is_array($selected)) {
                        $node_selected = false;
                        $visible = false;
                        foreach ($selected as $sel_node) {
                            if ($this->checkSelected($item[$this->_node_id], $sel_node)) {
                                $node_selected = true;
                            }
                            if ($this->checkVisible($item[$this->_node_id], $sel_node, $start)) {
                                $visible = true;
                            }
                        }
                        $item[$this->_node_selected] = $node_selected;
                        $item[$this->_node_visible] = $visible;
                    } else {
                        $item[$this->_node_selected] = $this->checkSelected($item[$this->_node_id], $selected);
                        $item[$this->_node_visible] = $this->checkVisible($item[$this->_node_id], $selected, $start);
                    }
                } else {
                    $item[$this->_node_selected] = false;
                    $item[$this->_node_visible] = $this->checkVisible($item[$this->_node_id], null, $start);
                }

                $item[$this->_node_children] = [];

                // Number of stack items
                $l = count($stack);

                // Check if we're dealing with different levels
                while ($l > 0 && $stack[$l - 1][$this->_node_level] >= $item[$this->_node_level]) {
                    array_pop($stack);
                    $l--;
                }

                // Stack is empty (we are inspecting the root)
                if ($l == 0) {
                    // Assigning the root child
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = &$trees[$i];
                } else {
                    // Add child to parent
                    $i = count($stack[$l - 1][$this->_node_children]);
                    $stack[$l - 1][$this->_node_children][$i] = $item;
                    // Node is a leaf if it has no children
                    $stack[] = &$stack[$l - 1][$this->_node_children][$i];
                }
            }
        }

        return $trees;
    }

    /**
     * [Re]calculates left and right values for nested set implementation
     */
    public function buildNestedSet()
    {
        $this->_temp = $this->makeMultiLevel(null, null, null);
        $right = 1;
        foreach ($this->_temp as $key => $item) {
            $right = $this->calcNestedSet($item, $right);
        }
        $this->_temp = null;
    }

    /**
     * @param array $parent
     * @param int   $left
     *
     * @return int
     */
    protected function calcNestedSet(array $parent, $left = 1)
    {
        $right = $left + 1;
        foreach ($parent[$this->_node_children] as $key => $item) {
            $right = $this->calcNestedSet($item, $right);
        }
        $this->_data[$parent[$this->_node_id]][$this->_node_left] = $left;
        $this->_data[$parent[$this->_node_id]][$this->_node_right] = $right;

        return $right + 1;
    }

    protected function addMetaData()
    {
        $this->_temp = array_values($this->_data);
        $keys = array_column($this->_temp, $this->_node_id);
        foreach ($this->_temp as $item) {
            $level = $item[$this->_node_level];
            $key = array_search($item[$this->_node_id], $keys);
            $prev = ($key > 0) ? $this->_temp[$key - 1] : null;
            $next = ($key < count($keys) - 1) ? $this->_temp[$key + 1] : null;
            $parent = $item[$this->_node_parent] ? $this->_data[$item[$this->_node_parent]] : null;

            $item[$this->_node_first] = false;
            $item[$this->_node_last] = false;
            $item[$this->_node_only] = false;

            if ($prev) {
                if ($level > $prev[$this->_node_level]) {
                    $item[$this->_node_first] = true;
                }
            } else {
                $item[$this->_node_first] = true;
            }
            if ($next) {
                if ($level > $next[$this->_node_level]) {
                    $item[$this->_node_last] = true;
                }
            } else {
                $item[$this->_node_last] = true;
            }
            if ($item[$this->_node_first] && $item[$this->_node_last]) {
                $item[$this->_node_only] = true;
            }

            $item[$this->_node_icons] = [];
            if ($level > 0) {
                if ($level > 1) {
                    if ($this->_data[$parent][$this->_node_last]) {

                        $item[$this->_node_icons][] = $this->_icon_empty;
                    } else {
                        $item[$this->_node_icons][] = $this->_icon_bar;
                    }


                    $item[$this->_node_icons] += array_fill(0, $level - 1, $this->_icon_bar);
                }

                if ($item[$this->_node_only]) {
                    $item[$this->_node_icons][] = $this->_icon_bottom;
                } elseif ($item[$this->_node_last]) {
                    $item[$this->_node_icons][] = $this->_icon_bottom;
                } else {
                    $item[$this->_node_icons][] = $this->_icon_join;
                }
            }

            $this->_data[$item[$this->_node_id]] = $item;
        }
    }

    /**
     * Gets the children of a node with a given id
     *
     * @param string $id
     * @param int    $level_limit If not null only up to this level will be returned.  Use 0 to get only root nodes.
     * @param null   $selected
     * @param null   $start
     *
     * @return array
     */
    protected function getChildren($id, $level_limit = null, $selected = null, $start = null)
    {
        $children = [];
        foreach ($this->_data as $item) {
            if ($item[$this->_node_parent] == $id) {
                if (!is_null($selected)) {
                    if (is_array($selected)) {
                        $node_selected = false;
                        $visible = false;
                        foreach ($selected as $sel_node) {
                            if ($this->checkSelected($item[$this->_node_id], $sel_node)) {
                                $node_selected = true;
                            }
                            if ($this->checkVisible($item[$this->_node_id], $sel_node, $start)) {
                                $visible = true;
                            }
                        }
                        $item[$this->_node_selected] = $node_selected;
                        $item[$this->_node_visible] = $visible;
                    } else {
                        $item[$this->_node_selected] = $this->checkSelected($item[$this->_node_id], $selected);
                        $item[$this->_node_visible] = $this->checkVisible($item[$this->_node_id], $selected, $start);
                    }
                } else {
                    $item[$this->_node_selected] = false;
                    $item[$this->_node_visible] = $this->checkVisible($item[$this->_node_id], null, $start);
                }
                if (is_null($level_limit) || $level_limit > $item[$this->_node_level]) {
                    $item[$this->_node_children] = $this->getChildren($item[$this->_node_id], $level_limit, $selected,
                        $start);
                } else {
                    $item[$this->_node_children] = [];
                }
                $children[$item[$this->_node_id]] = $item;
            }
        }

        return $children;
    }

    /**
     * Used to iterate through items
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }

    /**
     * Returns total number of nodes
     *
     * @return int
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * Clears data
     */
    protected function clearData()
    {
        $this->_data = [];
        $this->_temp = null;
    }

    /**
     * Compares two nodes by parent and sort column
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    protected function nodeSort($a, $b)
    {
        if ($a[$this->_node_parent] == $b[$this->_node_parent]) {
            // They have the same parent
            if (!is_null($this->_node_sort)) {
                // There is a sort column set
                if ($a[$this->_node_sort] == $b[$this->_node_sort]) {
                    return 0;
                }

                return ($a[$this->_node_sort] < $b[$this->_node_sort]) ? -1 : 1;
            } else {
                // There is no sort column set
                return 0;
            }
        }

        return ($a[$this->_node_parent] < $b[$this->_node_parent]) ? -1 : 1;
    }
}
