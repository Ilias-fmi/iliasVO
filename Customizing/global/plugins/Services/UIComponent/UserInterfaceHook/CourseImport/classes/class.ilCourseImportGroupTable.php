<?php
require_once './Services/Table/classes/class.ilTable2GUI.php';
/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 19.12.2016
 * Time: 12:34
 */
class ilCourseImportGroupTable extends ilTable2GUI{


    function __construct($a_parent_obj)
    {
        global $ilCtrl, $lng;

        parent::__construct($a_parent_obj);

        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
       // $this->setRowTemplate("tpl.my_row_template.html",
       //     "Service/MyService");     // could be a Module template, too
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
    function fillRow()
    {
      //  global $lng, $ilCtrl;


        $this->tpl->setVariable('course_title', "Kurs1");




    }

}