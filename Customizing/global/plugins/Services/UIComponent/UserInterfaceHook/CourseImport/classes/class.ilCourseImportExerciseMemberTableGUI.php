<?php
include_once './Modules/Exercise/classes/class.ilExerciseMemberTableGUI.php';
/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 15.02.2016
 * Time: 17:34
 *
 */
class ilCourseImportExerciseMemberTableGUI extends ilExerciseMemberTableGUI {


    protected $group;
    function __construct($a_parent_obj, $a_parent_cmd, $a_exc, $a_ass,$group_id)
    {
        $this->group = $group_id;
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_exc, $a_ass);
        
     
    }

    protected function fillRow($member)
    {
        if($this->isGroupMember($member,$this->group)){
        parent::fillRow($member);
        }
    }

    protected function isGroupMember($member,$group_id){
        global $ilDB;

        $user_id = $member['usr_id'];
        $data= array();
        $query = "select om.usr_id
        from ilias.obj_members as om
        where om.obj_id = '".$group_id."' and om.usr_id = '".$user_id."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }

        if(empty($data)){
            return false;
        }

        return true;

        }


}