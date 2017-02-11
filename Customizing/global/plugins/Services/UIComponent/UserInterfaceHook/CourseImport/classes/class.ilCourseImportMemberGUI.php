<?php
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Database/classes/class.ilDB.php';
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';

define('IL_GRP_MEMBER',5);

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 19.01.2017
 * Time: 12:40
 * @ilCtrl_IsCalledBy ilCourseImportMemberGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportMemberGUI: ilObjCourseAdministrationGUI
 * @ilCtrl_Calls      ilCourseImportMemberGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls      ilCourseImportMemberGUI: ilObjCourseGUI
 *

 */
class ilCourseImportMemberGUI {
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
    /**
     * @var ilTree
     */
    protected $tree;
    protected $course_id;

    protected $userLogin;
    protected $groupTitle;
    protected $destinationTitle;

    protected $group_title;
    protected $destination_title;

    public function __construct() {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->course_id = $_GET['ref_id'];
        $this->ilLocator = $ilLocator;
        $this->pl = ilCourseImportPlugin::getInstance();
    }
    protected function prepareOutput() {

        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupgui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportmembergui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $_GET['ref_id']);

        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit',$this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportMemberGUI')));
        $this->tabs->activateSubTab('member_edit');

        $this->ctrl->getRedirectSource();

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilrepositorygui',
            'ilrepositorygui',
        )));

        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }
    protected function setTitleAndIcon() {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_acop'));
        $this->tpl->setDescription($this->pl->txt('obj_acop_desc'));
    }
    /**
     *
     */
    public function executeCommand() {
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
    protected function view() {

        $form = $this->initForm();
        $this->tpl->setContent($form->getHTML());
    }

    protected function initForm(){
        global $lng, $ilCtrl;
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('member_edit'));
        $form->setDescription($this->pl->txt('member_description'));
        $form->setId('member_edit');
        $form->setFormAction($this->ctrl->getFormAction($this));

        $a_options = array(
            'auto_complete_name'	=> $lng->txt('user'),
        );

        $ajax_url = $ilCtrl->getLinkTargetByClass(array(get_class($this),'ilRepositorySearchGUI'),
            'doUserAutoComplete', '', true,false);

        include_once("./Services/Form/classes/class.ilTextInputGUI.php");
        $this->userLogin = new ilTextInputGUI($a_options['auto_complete_name'], 'user_login');
        $this->userLogin->setDataSource($ajax_url);

        $this->groupTitle = new ilSelectInputGUI($this->pl->txt('group_title'), 'group_title');
        $this->groupTitle->setOptions($this->getGroups());

        $this->destinationTitle = new ilSelectInputGUI($this->pl->txt('destination_title'), 'destination_title');
        $this->destinationTitle->setOptions($this->getGroups());

        $this->userLogin->setRequired(true);
        $this->groupTitle->setRequired(true);
        $this->destinationTitle->setRequired(true);

        $form->addItem($this->userLogin);
        $form->addItem($this->groupTitle);
        $form->addItem($this->destinationTitle);
        $form->addCommandButton('moveMember', $this->pl->txt('move_member'));

        return $form;
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

    protected function moveMember(){
        $form = $this->initForm();
        $form->setValuesByPost();

        $member_login = $this->userLogin->getValue();
        $options = $this->groupTitle->getOptions();
        $this->group_title = $options[$this->groupTitle->getValue()];
        $this->destination_title = $options[$this->destinationTitle->getValue()];

        $member_id = $this->getMemberIdByLogin($member_login);
        $group_id = $this->getGroupIdByTitle($this->group_title,$this->course_id);
        $destination_id = $this->getGroupIdByTitle($this->destination_title,$this->course_id);

        $ref = $destination_id[0];
        $ref2 = $group_id[0];

        $description_dest = "Groupmember of group obj_no." . $ref["obj_id"];
        $description_source = "Groupmember of group obj_no." . $ref2["obj_id"];

        $role_id_dest = $this->getRoleID($description_dest);
        $role_id_source = $this->getRoleID($description_source);

        //Ueberpruefung der Daten auf Korrektheit vor DB-Zugriff
        //$this->checkIfGroupExists($group_id[0]);                       //alte Gruppe vorhanden
        //$this->checkIfGroupExists($destination_id[0]);                 //neue Gruppe vorhanden
        //$this->checkIfUserExistsInGroup($member_id[0], $group_id[0]);             //User in alter Gruppe vorhanden
        //$this->checkIfUserNotExistsInGroup($member_id[0], $destination_id[0]);    //User in neuer Gruppe vorhanden

        if (($this->checkIfGroupExists($group_id[0])) and ($this->checkIfGroupExists($destination_id[0])) and
            ($this->checkIfUserExistsInGroup($member_id[0], $group_id[0])) and
            ($this->checkIfUserNotExistsInGroup($member_id[0], $destination_id[0]))) {

            $this->manipulateDB($member_id[0],$role_id_source[0],$destination_id[0],$role_id_dest[0],$group_id[0]);

        }

        $this->view();

    }
    protected function getRoleID($description){
        global $ilDB;
        $role_id = array();
        $query = "SELECT od.obj_id FROM ilias.object_data as od WHERE od.description = '".$description."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($role_id,$record);
        }
        return $role_id;
    }
    protected function manipulateDB($member_id,$role_id_source,$destination_id,$role_id_dest,$source_id){
        global $ilDB;

        //insert in RBAC
        $query = "INSERT INTO rbac_ua (usr_id, rol_id) ".
            "VALUES (".$member_id['usr_id'].",".$role_id_dest['obj_id'].")";
        $res = $ilDB->manipulate($query);

        //delete OLD from RBAC
        $query = "DELETE FROM rbac_ua 
            WHERE usr_id = ".$member_id['usr_id']."
            AND rol_id = ".$role_id_source['obj_id']." ";
        $res = $ilDB->manipulate($query);

        $query = "UPDATE ilias.obj_members as om
        SET om.obj_id = '".$destination_id['obj_id']."' WHERE om.usr_id = '".$member_id['usr_id']."' AND om.obj_id = '"
            .$source_id['obj_id']."' AND om.member = 1";
        $ilDB->manipulate($query);


        ilUtil::sendSuccess($this->userLogin->getValue().$this->pl->txt("movedSuccessful").
            $this->group_title.$this->pl->txt("movedTo").$this->destination_title);

    }
    protected function getGroupIdByTitle($group_title,$course_id){
        global $ilDB;
        $group_id= array();

        $query = "select oref.obj_id from ilias.crs_items as citem
                  join ilias.object_reference as oref on oref.ref_id = citem.obj_id
                  join ilias.object_data as od on oref.obj_id = od.obj_id                  
                  join ilias.crs_items as ci on oref.ref_id = ci.obj_id
                  where od.title='".$group_title."' and ci.parent_id='".$_GET['ref_id']."' and oref.deleted is null";
        $result = $ilDB->queryF($query, array('text','integer'),array($group_title,$course_id));
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($group_id,$record);
        }

        return $group_id;
    }

    protected function getMemberIdByLogin($member_login){
        global $ilDB;

        $member_id = array();
        $query = "select usr_id from ilias.usr_data as ud where (ud.login='".$member_login."')";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($member_id,$record);
        }

        return $member_id;

    }

    //Uberpruefung, ob User in der bisherigen Gruppe vorhanden
    protected function checkIfUserExistsInGroup($member_id, $group_id){
        global $ilDB;

        $queryResult = array();

        $query = "SELECT COUNT(*) FROM ilias.object_data as od 
                  join ilias.object_reference as obr on obr.obj_id = od.obj_id 
                  join ilias.obj_members as om on obr.obj_id = om.obj_id
                  WHERE obr.deleted is null and od.obj_id = '".$group_id["obj_id"]."' and om.usr_id = '".$member_id["usr_id"]."'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($queryResult,$record);
        }

        if($queryResult[0]["count(*)"] != 1) {
            ilUtil::sendFailure($this->pl->txt("userInGroupNotExistent"), true);
            return false;
        } else {
            return true;
        }
    }

    //Uberpruefung, ob User in der neuen Gruppe schon vorhanden
    protected function checkIfUserNotExistsInGroup($member_id, $group_id){
        global $ilDB;

        $queryResult = array();

        //var_dump($member_id);

        $query = "SELECT COUNT(*) FROM ilias.object_data as od 
                  join ilias.object_reference as obr on obr.obj_id = od.obj_id 
                  join ilias.obj_members as om on obr.obj_id = om.obj_id
                  WHERE obr.deleted is null and od.obj_id = '".$group_id["obj_id"]."' and om.usr_id = '".$member_id["usr_id"]."'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($queryResult,$record);
        }

        if($queryResult[0]["count(*)"] == 1) {
            ilUtil::sendFailure($this->pl->txt("userInGroupExistent"), true);
            return false;
        } else {
            return true;
        }

    }


    // Ueberpruefung, ob die Gruppe exisitiert
    protected function checkIfGroupExists($group_id){
        global $ilDB;

        $queryResult = array();

        //var_dump($group_id);

        $query = "SELECT COUNT(*) FROM ilias.object_data as od 
                  join ilias.object_reference as obr on obr.obj_id = od.obj_id 
                  WHERE obr.deleted is null and od.obj_id = '".$group_id["obj_id"]."'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($queryResult,$record);
        }

        //var_dump($queryResult);

        if($queryResult[0]["count(*)"] != 1) {
            ilUtil::sendFailure($this->pl->txt("groupNotExistent"), true);
            return false;
        } else {
            return true;
        }
    }

    protected function getGroups(){

        global $ilDB;

        $data = array();
        $query = "select od.title as 'title'
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$_GET['ref_id']."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }

        $output = array();

        foreach ($data as $result){

            array_push($output, $result['title']);

        }

        return $output;
    }

    protected  function getGroupsWhereMember(){

        global $ilDB;

        $data = array();
        $query = "select od.title as 'title'
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    join ilias.obj_members as om on om.obj_id = oref.obj_id
                    join ilias.usr_data as ud on ud.usr_id = om.usr_id
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$_GET['ref_id'].
                        "' and om.usr_id='".$this->userLogin->getValue()."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }

        $output = array();

        foreach ($data as $result){

            array_push($output, $result['title']);

        }

        return $output;

    }

    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}