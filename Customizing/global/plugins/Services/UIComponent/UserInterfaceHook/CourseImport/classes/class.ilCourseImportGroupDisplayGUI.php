<?php
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'class.ilCourseImportGroupTable.php';
require_once './Services/Form/classes/class.ilDateTimeInputGUI.php';
require_once './Services/Form/classes/class.ilDateDurationInputGUI.php';


/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 16.12.2016
 * Time: 13:50
 * @ilCtrl_IsCalledBy ilCourseImportGroupDisplayGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportGroupDisplayGUI: ilObjCourseAdministrationGUI
 * @ilCtrl_Calls      ilCourseImportGroupDisplayGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls      ilCourseImportGroupDisplayGUI: ilObjCourseGUI
 * 
 * 
 * This class implements the functionality of the tab "Kurs bearbeiten" or "edit course"
 * Which inlcludes the query to get the actual values of the following variables and the 
 * update query for them too.
 * $obj_id float
 * $title string
 * $description string
 * $tutor string
 * $members float Maximum Members
 * $reg_start ilDateTime Start of Registration Period
 * $reg_end ilDateTime Start of Registration Period
 */
class ilCourseImportGroupDisplayGUI
{
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilTemplate
     */
    protected $tpl;
    /**
     * @var ilCourseImportPlugin
     */
    protected $pl;
    /**
     * @var ilTabsGUI
     */
    protected $tabs;
    /**
     * @var ilLocatorGUI
     */
    protected $ilLocator;
    /**
     * @var ilLanguage
     */
    protected $lng;

    protected $course_table;
    /**
     * @var ilTree
     */
    protected $tree;

    protected $form;

    protected $group_admins;

    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->group_admins = array();
        $this->pl = ilCourseImportPlugin::getInstance();
    }

    protected function prepareOutput()
    {
        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupgui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportmembergui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $_GET['ref_id']);


        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit',$this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportMemberGUI')));

        $this->tabs->activateSubTab('course_edit');

        $this->ctrl->getRedirectSource();

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilrepositorygui',
            'ilrepositorygui',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }

    protected function setTitleAndIcon()
    {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_acop'));
        $this->tpl->setDescription($this->pl->txt('obj_acop_desc'));
    }

    /**
     *
     */
    public function executeCommand()
    {
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();

        switch ($cmd) {
            default:
                $this->$cmd();
                break;
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }


    /**
     * default command
     */
    protected function view()
    {
        $this->form =$this->initForm();
        $this->tpl->setContent($this->form->getHTML());

    }

    /**
     * @return ilPropertyFormGUI
     */
    protected function initForm()
        {
            global $ilCtrl;

            $form = new ilPropertyFormGUI();
            $form->setTitle($this->pl->txt('course_edit'));
            $form->setFormAction($this->ctrl->getFormAction($this));
            $data = $this->getTableData($_GET['ref_id']);
            $a_options = array(
                'auto_complete_name'	=> $this->pl->txt('group_tutor'),
            );
            $ajax_url = $ilCtrl->getLinkTargetByClass(array(get_class($this),'ilRepositorySearchGUI'),
                'doUserAutoComplete', '', true,false);
            $n = 1;


        foreach ($data as $row){
            $section = new ilFormSectionHeaderGUI();
            $section->setTitle($row['title']);
            $form->addItem($section);
            $ref_id_field = new ilNumberInputGUI($this->pl->txt("ref_id"), "ref_id".$n);
            $textfield_name = new ilTextInputGUI($this->pl->txt("group_name"), "group_name".$n);
            $textfield_description = new ilTextInputGUI($this->pl->txt("group_description"),"description".$n);
            $textfield_tutor = new ilTextInputGUI($a_options['auto_complete_name'], 'tutor'.$n);
            $textfield_tutor->setDataSource($ajax_url);

            $textfield_members = new ilNumberInputGUI($this->pl->txt("group_max_members"),"members".$n);
            
            $time_limit = new ilCheckboxInputGUI($this->pl->txt('grp_reg_limited'),'reg_limit_time'.$n);
            $this->tpl->addJavaScript('./Services/Form/js/date_duration.js');
            $dur = new ilDateDurationInputGUI($this->pl->txt('grp_reg_period'),'reg'.$n);
            $dur->setStartText($this->pl->txt('cal_start'));
            $dur->setEndText($this->pl->txt('cal_end'));
            $dur->setShowTime(true);
            $ref_id_field->setValue($row['obj_id']);
            $ref_id_field->setHiddenTitle($row['obj_id']);
            $textfield_name->setValue($row['title']);
            $textfield_description->setValue($row['description']);
            $textfield_tutor->setValue($row['login']);
            $textfield_members->setValue($row['registration_max_members']);
            $time_limit->setChecked(!$row['registration_unlimited']);
            var_dump(!$row['registration_unlimited']);
            $start_time = new ilDateTime($row['registration_start'],IL_CAL_DATETIME);
            $end_time = new ilDateTime($row['registration_end'],IL_CAL_DATETIME);
            $this->group_admins[$row['obj_id']]=$row['login'];
            $dur->setStart($start_time);
            $dur->setEnd($end_time);
            $form->addItem($ref_id_field);
            $form->addItem($textfield_name);
            $form->addItem($textfield_description);

            $form->addItem($textfield_tutor);

            $time_limit->addSubItem($dur);
            $form->addItem($textfield_members);
            $form->addItem($time_limit);


            
            $n=$n+1;

        }
        $form->addCommandButton('saveGroups',$this->pl->txt('save_groups'));
            return $form;
    }

    protected function saveGroups(){

             
        $this->form = $this->initForm();
        $this->form->setValuesByPost();
        $items = $this->form->getItems();
        $group_items = array_chunk($items,7);
        $n=1;
        foreach ($group_items as $group){

            $ref_id = $group[1]->getHiddenTitle();
            $title = $group[2]->getValue();
            $description =$group[3]->getValue();
            $tutor=$group[4]->getValue();
            $members=$group[5]->getValue();
            $duration=$group[6];
            $time_reg_enabled = $duration->getChecked();
            $reg_start=$this->loadDate('reg'.$n,'start');
            $reg_end = $this->loadDate('reg'.$n,'end');
            $reg_start = $reg_start->get(IL_CAL_DATETIME);
            $reg_end = $reg_end->get(IL_CAL_DATETIME);

            
       

            
            //if reg_end before reg_start, we set the reg_start on the reg_end
            //maybe not the best solution 
            if($reg_end<$reg_start){
                
                $reg_end = $reg_start;
            }

            $this->updateGroup($ref_id,$title,$description,$tutor,$members,$reg_start,$reg_end,$time_reg_enabled);
            $n=$n+1;
        }


        $this->form = $this->initForm();
        $this->tpl->setContent($this->form->getHTML());


    }
    protected function getUserId($user_login){
        global $ilDB;
        $data = array();
        $query = "select ud.usr_id 
        from ilias.usr_data as ud
        where ud.login = '".$user_login."'";
        $res = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($res)){
            array_push($data,$record);
        }

        return $data[0];

    }

    protected function getAdminRoleId($obj_id){

        $description = "Groupadmin group obj_no." . $obj_id;
        global $ilDB;
        $role_id = array();
        $query = "SELECT od.obj_id FROM ilias.object_data as od WHERE od.description = '".$description."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($role_id,$record);
        }
        return $role_id[0];
    }

    protected function getCourseAdminIDs($ref_id){
        global $ilDB;
        $ids = array();
        $data = array();
        $query = "select om.usr_id from ilias.obj_members as om
join ilias.object_reference as oref on oref.obj_id = om.obj_id
where om.admin = 1 and oref.ref_id = '".$ref_id."'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        foreach ($data as $row){
            array_push($ids,$row['usr_id']);
        }
        return $ids;

    }
    protected function loadDate($a_comp,$a_field)
    {
        global $ilUser;

        include_once('./Services/Calendar/classes/class.ilDateTime.php');

        $dt['year'] = (int) $_POST[$a_comp][$a_field]['date']['y'];
        $dt['mon'] = (int) $_POST[$a_comp][$a_field]['date']['m'];
        $dt['mday'] = (int) $_POST[$a_comp][$a_field]['date']['d'];
        $dt['hours'] = (int) $_POST[$a_comp][$a_field]['time']['h'];
        $dt['minutes'] = (int) $_POST[$a_comp][$a_field]['time']['m'];
        $dt['seconds'] = (int) $_POST[$a_comp][$a_field]['time']['s'];

        $date = new ilDateTime($dt,IL_CAL_FKT_GETDATE,$ilUser->getTimeZone());
        return $date;
    }


    /**
     * @param $obj_id float
     * @param $title string
     * @param $description string
     * @param $tutor string
     * @param $members float Maximum Members
     * @param $reg_start ilDateTime Start of Registration Period
     * @param $reg_end ilDateTime Start of Registration Period
     */
    protected function updateGroup($obj_id, $title, $description, $tutor, $members, $reg_start, $reg_end, $time_reg){
        
        global $ilDB,$rbacadmin;


        //MANIPULATE GROUP ADMIN needs to be checked
        //Update Group Admins if necessary
        if($this->group_admins[$obj_id]!=$tutor){

            $user_id = $this->getUserId($tutor);
            $user_id = $user_id['usr_id'];

            $role_id = $this->getAdminRoleId($obj_id);
            $role_id = $role_id['obj_id'];


            $course_admins = $this->getCourseAdminIDs($_GET['ref_id']);


            $rbacadmin->assignUser($role_id,$user_id);

            if((!empty($this->group_admins[$obj_id]))){
                $user_id_old = $this->getUserId($this->group_admins[$obj_id]);
                $user_id_old = $user_id_old['usr_id'];

                if(!in_array($user_id_old,$course_admins)){

                    $rbacadmin->deassignUser($role_id,$user_id_old);

                     //Update Obj_members
//                    $query = "UPDATE ilias.obj_members as om
//                    SET  om.usr_id = '".$user_id."' WHERE
//                    om.obj_id = '".$obj_id."' AND om.admin = 1 AND om.usr_id = '".$user_id_old."'";
//                    $ilDB->manipulate($query);
                    }else{
//                    $query = "INSERT INTO ilias.obj_members (obj_id,usr_id,blocked,notification,passed,origin,origin_ts,contact,admin,tutor,member)
//                    VALUES (".$obj_id.",".$user_id.",0,0,NULL,0,0,0,1,0,0)";
//                    $ilDB->manipulate($query);
                }


            }else{
//                $query = "INSERT INTO ilias.obj_members (obj_id,usr_id,blocked,notification,passed,origin,origin_ts,contact,admin,tutor,member)
//                VALUES (".$obj_id.",".$user_id.",0,0,NULL,0,0,0,1,0,0)";
//                $ilDB->manipulate($query);

            }
        }

        
        $query1 = "UPDATE ilias.object_data as od
                           
                SET od.title = '".$title."' , od.description = '".$description."'
                             
                WHERE od.obj_id = '".$obj_id."'";
        
       $query2 = "UPDATE ilias.grp_settings as gs
        
                   SET gs.registration_max_members ='".$members."'
        
                 WHERE gs.obj_id = '".$obj_id."'";
        
       


       if($time_reg=="1") {
       $query3 = "UPDATE ilias.grp_settings as gs

                  SET gs.registration_start = '".$reg_start."', gs.registration_unlimited = 0

               WHERE gs.obj_id = '".$obj_id."'";

       $query4 = "UPDATE ilias.grp_settings as gs
               SET gs.registration_end = '".$reg_end."'
              WHERE gs.obj_id = '".$obj_id."'" ;

            $ilDB->manipulate($query3);
            $ilDB->manipulate($query4);
       }


            $ilDB->manipulate($query1);
            $ilDB->manipulate($query2);

    }

      
    protected function getTableData($ref_id){

        global $ilDB;

        $data = array();
        $query = "select od.title, gs.registration_max_members, ud.login, od.description, gs.registration_start, gs.registration_end, od.obj_id, gs.registration_unlimited
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id 
                    join ilias.grp_settings gs on gs.obj_id = oref.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    left join (select * from ilias.obj_members om where om.admin = 1) as obm on obm.obj_id = oref.obj_id
                    left join ilias.usr_data ud on ud.usr_id = obm.usr_id
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$ref_id."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        return $data;

    }

    /**
     * Do auto completion
     * @return void
     */
    protected function doUserAutoComplete()
    {


        $a_fields = array('login','firstname','lastname','email');
        $result_field = 'login';


        include_once './Services/User/classes/class.ilUserAutoComplete.php';
        $auto = new ilUserAutoComplete();

        if(($_REQUEST['fetchall']))
        {
            $auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
        }

        $auto->setSearchFields($a_fields);
        $auto->setResultField($result_field);
        $auto->enableFieldSearchableCheck(true);

        echo $auto->getList($_REQUEST['term']);
        exit();
    }

    protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}
