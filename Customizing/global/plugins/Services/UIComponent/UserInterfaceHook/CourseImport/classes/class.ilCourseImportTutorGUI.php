<?php
include_once 'class.ilCourseImportExerciseMemberTableGUI.php';
include_once './Modules/Exercise/classes/class.ilExerciseManagementGUI.php';
include_once "Modules/Exercise/classes/class.ilExSubmission.php";
include_once "Modules/Exercise/classes/class.ilExSubmissionBaseGUI.php";
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
include_once("./Services/Form/classes/class.ilSelectInputGUI.php");

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 15.02.2017
 * Time: 12:06
 * @ilCtrl_IsCalledBy ilCourseImportTutorGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportTutorGUI: ilObjExerciseGUI, ilExSubmissionFileGUI, ilFileSystemGUI, ilRepositorySearchGUI
 */

class ilCourseImportTutorGUI extends ilExerciseManagementGUI {

    const VIEW_ASSIGNMENT = 1;
    const VIEW_PARTICIPANT = 2;
    const VIEW_GRADES = 3;

    protected $tree;
    protected $lng;
    protected $tabs;
    protected $ilLocator;
    protected $tpl;
    protected $ctrl;
    protected $pl;
    protected $exercise;
    protected $assignment;
    protected $group;
    protected $si;

    /**
     * ilCourseImportTutorGUI constructor.
     */
    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        require_once "./Modules/Exercise/classes/class.ilObjExerciseGUI.php";
        $ex_gui =& new ilObjExerciseGUI("", (int) $_GET["ref_id"], true, false);
        $this->exercise=$ex_gui->object;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $ilCtrl->saveParameter($this, array("vw", "member_id"));
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilCourseImportPlugin::getInstance();
    }

    protected function prepareOutput() {

        global $ilLocator, $tpl;


        $this->ctrl->setParameterByClass('ilobjexercisegui', 'ref_id', $_GET['ref_id']);



        $this->ctrl->getRedirectSource();

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilobjexercisegui',
            'ilobjexercisegui',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }

    protected function setTitleAndIcon() {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_exc.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_extu'));
        $this->tpl->setDescription($this->pl->txt('obj_extu_desc'));
    }

    /**
     *
     */
    public function executeCommand() {
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();

        var_dump($cmd);

        switch ($cmd) {
            case 'view':
                $this->view();
                break;
            default:
                $this->{$cmd."Object"}();
                break;
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }



    /**
     * default command
     */
    protected function view() {
        $this->membersObject();

    }

    protected function isCourse($ref_id){
        global $ilDB;

        $data = array();
        $query = "select od.title
                    from ilias.object_data as od 
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    where od.type = 'crs' and oref.ref_id = '".$ref_id."' ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        if(empty($data)){
            return false;
        }
        return true;

    }

    protected function getParentIds($id){

        global $ilDB;

        $ids = array();
        $data = array();
        $query = "select tree.parent from ilias.tree as tree where child = '".$id."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        foreach ($data as $folder){
            array_push($ids,$folder['parent']);
        }
        return $ids;

    }

    protected function getGroups(){
        global $ilUser, $ilDB;
        $user_id = $ilUser->getId();

        $ref_id = $_GET['ref_id'];

        do {
            $parent_id = $this->getParentIds($ref_id);
            $ref_id = $parent_id[0];
        }while (!$this->isCourse($ref_id));


        $data = array();
        $query = "select od.title, od.obj_id
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    join ilias.obj_members as om on om.obj_id = oref.obj_id 
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$ref_id."' and om.usr_id = '".$user_id."' and om.admin = 1 ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        $output = array();
        foreach ($data as $result){

            $output[$result['obj_id']]= $result['title'];
        }

        return $output;
    }

    protected function selectGroupObject(){


        $this->group = ilUtil::stripSlashes($_POST["grp_id"]);
        var_dump($this->group);
        $this->membersObject();
    }

    public function membersObject(){
        global $tpl, $ilCtrl,$ilToolbar, $lng;

        require_once "./Modules/Exercise/classes/class.ilObjExerciseGUI.php";
        $ex_gui =& new ilObjExerciseGUI("", (int) $_GET["ref_id"], true, false);
        $this->exercise=$ex_gui->object;

        $group_options = $this->getGroups();

        include_once 'Services/Tracking/classes/class.ilLPMarks.php';


        $this->si = new ilSelectInputGUI($this->lng->txt(""), "grp_id");
        $this->si->setOptions($group_options);
        if (!empty($this->group)) {
            $this->si->setValue($this->group);
        }
        $ilToolbar->addStickyItem($this->si);

        var_dump($this->si->getOptions());

        include_once("./Services/UIComponent/Button/classes/class.ilSubmitButton.php");
        $button = ilSubmitButton::getInstance();
        $button->setCaption("exc_select_grp");
        $button->setCommand("selectGroup");
        $ilToolbar->addStickyItem($button);

        $ilToolbar->addSeparator();

        // assignment selection
        include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());


        if (!$this->assignment)
        {
            $this->assignment = current($ass);
        }

        reset($ass);
        if (count($ass) > 1)
        {
            $options = array();
            foreach ($ass as $a)
            {
                $options[$a->getId()] = $a->getTitle();
            }
            include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
            $si = new ilSelectInputGUI($this->lng->txt(""), "ass_id");
            $si->setOptions($options);
            $si->setValue($this->assignment->getId());
            $ilToolbar->addStickyItem($si);

            include_once("./Services/UIComponent/Button/classes/class.ilSubmitButton.php");
            $button = ilSubmitButton::getInstance();
            $button->setCaption("exc_select_ass");
            $button->setCommand("selectAssignment");
            $ilToolbar->addStickyItem($button);

            $ilToolbar->addSeparator();
        }
        // #16165 - if only 1 assignment dropdown is not displayed;
        else if($this->assignment)
        {
            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());
        }

        // #16168 - no assignments
        if (count($ass) > 0)
        {

            // we do not want the ilRepositorySearchGUI form action
            $ilToolbar->setFormAction($ilCtrl->getFormAction($this));

            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());

            if(ilExSubmission::hasAnySubmissions($this->assignment->getId()))
            {

                if($this->assignment->getType() == ilExAssignment::TYPE_TEXT)
                {
                    $ilToolbar->addFormButton($lng->txt("exc_list_text_assignment"), "listTextAssignment");
                }
                else
                {
                    $ilToolbar->addFormButton($lng->txt("download_all_returned_files"), "downloadAll");
                }
            }
            $this->ctrl->setParameter($this, "vw", self::VIEW_ASSIGNMENT);

            include_once("./Modules/Exercise/classes/class.ilExerciseMemberTableGUI.php");
            $exc_tab = new ilCourseImportExerciseMemberTableGUI($this, "members", $this->exercise, $this->assignment, $this->group);
            $tpl->setContent($exc_tab->getHTML());
        }
        else
        {
            ilUtil::sendInfo($lng->txt("exc_no_assignments_available"));
        }

        $ilCtrl->setParameter($this, "ass_id", "");

        //$this->tpl->setContent($ilToolbar->getHTML());
        return;
    }
    protected function selectAssignment(){

            global $ilTabs;

            var_dump($_GET["ass_id"]);
            $_GET["ass_id"] = ilUtil::stripSlashes($_POST["ass_id"]);
            var_dump($_GET["ass_id"]);

            $this->members();

    }

    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}