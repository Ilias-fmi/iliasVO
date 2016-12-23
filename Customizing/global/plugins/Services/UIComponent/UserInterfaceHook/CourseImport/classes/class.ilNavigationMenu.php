<?php
include_once("./Services/UIComponent/Explorer2/classes/class.ilExplorerBaseGUI.php");

/**
 * Created by PhpStorm.
 * User: Kai Durst
 * Date: 21.12.2016
 * Time: 12:59
 */
class ilNavigationMenu extends ilExplorerBaseGUI
{
    private $nodes = array(
        0 => array("id" => 0,
            "content" => "Root Node",
            "childs" => array(1, 2)),
        1 => array("id" => 1,
            "content" => "First Node",
            "childs" => array()),
        2 => array("id" => 2,
            "content" => "Second Node",
            "childs" => array(3)),
        3 => array("id" => 3,
            "content" => "Third Node",
            "childs" => array())
    );

    //
    // Abstract function that need to be overwritten in derived classes
    //

    /**
     * Get root node.
     *
     * @return mixed root node object/array
     */
    function getRootNode()
    {
        return $this->nodes[0];
    }

    /**
     * Get childs of node
     *
     * @param string $a_parent_id parent node id
     * @return array childs
     */
    function getChildsOfNode($a_parent_node_id)
    {
        $childs = array();
        foreach ($this->nodes[$a_parent_node_id]["childs"] as $c)
        {
            $childs[] = $this->nodes[$c];
        }
        return $childs;
    }

    /**
     * Get content of a node
     *
     * @param mixed $a_node node array or object
     * @return string content of the node
     */
    function getNodeContent($a_node)
    {
        return $a_node["content"];
    }

    /**
     * Get id of a node
     *
     * @param mixed $a_node node array or object
     * @return string id of node
     */
    function getNodeId($a_node)
    {
        return $a_node["id"];
    }

}