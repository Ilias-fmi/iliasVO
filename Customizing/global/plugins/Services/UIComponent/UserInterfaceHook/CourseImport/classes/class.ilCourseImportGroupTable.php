<?php
require_once './Services/Table/classes/class.ilTable2GUI.php';
/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 19.12.2016
 * Time: 12:34
 *
 *  Is needed for the edit course tab
 */
class ilCourseImportGroupTable extends ilTable2GUI{


    function __construct($a_parent_obj)
    {
        global $ilCtrl, $lng;

        parent::__construct($a_parent_obj);

        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate('tpl.obj_tbl_rows.html');
        // could be a Module template, too
        
     
    }

    /**
     * Get data and put it into an array
     */
    function getMyDataFromDb()
    {
    //TODO
    }

    /**
     * Fill a single data row.
     */
    function fillRow($a_set)
    {
        $this->tpl->setVariable('title',$a_set['title']);
        $this->tpl->setVariable('description',$a_set['description']);
        $this->tpl->setVariable('login',$a_set['login']);
        $this->tpl->setVariable('registration_max_members',$a_set['registration_max_members']);



    }

}