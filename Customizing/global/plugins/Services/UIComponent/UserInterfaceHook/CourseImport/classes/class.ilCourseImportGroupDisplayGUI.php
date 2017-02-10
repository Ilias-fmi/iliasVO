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

    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
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
        $items = $this->form->getItems();


        //Ausgabe zu Testzwecken, richtige Funktion in saveGroups(); !!!!!
        $group_items = array_chunk($items,7);
        foreach ($group_items as $group){

            $ref_id = $group[1]->getValue();
            $title = $group[2]->getValue();
            $description =$group[3]->getValue();
            $tutor=$group[4]->getValue();
            $members=$group[5]->getValue();
            $duration=$group[6];
            var_dump($ref_id);
            var_dump($title);
            var_dump($description);
            var_dump($tutor);
            var_dump($members);
        }
        $this->tpl->setContent($this->form->getHTML());

    }

    /**
     * @return ilPropertyFormGUI
     */
    protected function initForm()
        {
            global $lng, $ilCtrl;

            $form = new ilPropertyFormGUI();
            $form->setTitle($this->pl->txt('course_edit'));
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
            $ref_id_field = new ilNumberInputGUI($this->pl->txt("ref_id"), "ref_id");
            $ref_id_field->setDisabled(true);
            $textfield_name = new ilTextInputGUI($this->pl->txt("group_name"), "group_name");
            $textfield_description = new ilTextInputGUI($this->pl->txt("group_description"),"description");
            //$textfield_tutor = new ilUserLoginInputGUI($this->pl->txt("group_tutor"),"tutor");
            $textfield_tutor = new ilTextInputGUI($a_options['auto_complete_name'], 'tutor'.$n);
            $textfield_tutor->setDataSource($ajax_url);

            $textfield_members = new ilNumberInputGUI($this->pl->txt("group_max_members"),"members");
            $this->tpl->addJavaScript('./Services/Form/js/date_duration.js');
            $dur = new ilDateDurationInputGUI($this->pl->txt('grp_reg_period'),'reg');
            $dur->setStartText($this->pl->txt('cal_start'));
            $dur->setEndText($this->pl->txt('cal_end'));
            $dur->setShowTime(true);
            $ref_id_field->setValue($row['obj_id']);
            $textfield_name->setValue($row['title']);
            $textfield_description->setValue($row['description']);
            $textfield_tutor->setValue($row['login']);
            $textfield_members->setValue($row['registration_max_members']);
            $start_time = new ilDateTime($row['registration_start'],IL_CAL_DATETIME);
            $end_time = new ilDateTime($row['registration_end'],IL_CAL_DATETIME);
            $dur->setStart($start_time);
            $dur->setEnd($end_time);
            $form->addItem($ref_id_field);
            $form->addItem($textfield_name);
            $form->addItem($textfield_description);

            $form->addItem($textfield_tutor);

            $form->addItem($textfield_members);
            $form->addItem($dur);
            $n=$n+1;

        }
        $form->addCommandButton('saveGroups',$this->pl->txt('save_groups'));
            return $form;
    }

    protected function saveGroups(){


        $items = $this->form->getItems();
        $group_items = array_chunk($items,7);
        var_dump($group_items);
        $form = new ilPropertyFormGUI();
        foreach ($group_items as $group){

            $ref_id = $group[1]->getValue();
            $title = $group[2]->getValue();
            $description =$group[3]->getValue();
            $tutor=$group[4]->getValue();
            $members=$group[5]->getValue();
            $duration=$group[6];
            $reg_start=$duration->getStart();
            $reg_end=$duration->getEnd();
            var_dump($ref_id);
            var_dump($title);
            var_dump($description);
            var_dump($tutor);
            var_dump($members);


            $this->updateGroup($ref_id,$title,$description,$tutor,$members,$reg_start,$reg_end);
        }


        $this->tpl->setContent($form->getHTML());


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
    protected function updateGroup($obj_id, $title, $description, $tutor, $members, $reg_start, $reg_end){

        $query = "update object_data
        
                    set od.title = '".$title."'         
         
                    from ilias.object_data as od
                    
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id 
                    join ilias.grp_settings gs on gs.obj_id = oref.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    left join (select * from ilias.obj_members om where om.admin = 1) as obm on obm.obj_id = oref.obj_id
                    left join ilias.usr_data ud on ud.usr_id = obm.usr_id                    
                    
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$_GET['ref_id']."'";
                    
                    //od.title, gs.registration_max_members, ud.login, od.description, gs.registration_start, gs.registration_end, od.obj_id

    }



    protected function getTableData($ref_id){

        global $ilDB;

        $data = array();
        $query = "select od.title, gs.registration_max_members, ud.login, od.description, gs.registration_start, gs.registration_end, od.obj_id
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