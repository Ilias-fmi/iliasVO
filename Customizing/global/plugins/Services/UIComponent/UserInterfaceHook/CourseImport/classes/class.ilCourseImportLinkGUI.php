<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Services/UIComponent/Explorer2/classes/class.ilExplorerBaseGUI.php");
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
require_once './Modules/Group/classes/class.ilObjGroup.php';
require_once './Services/Object/classes/class.ilObject2.php';
require_once './Services/Form/classes/class.ilNumberInputGUI.php';
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Database/classes/class.ilDB.php';
require_once './Services/Form/classes/class.ilRadioGroupInputGUI.php';
require_once './Services/Form/classes/class.ilRadioOption.php';
require_once './Services/Form/classes/class.ilDateTimeInputGUI.php';
/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 06.02.2017
 * Time: 14:16
 * @ilCtrl_IsCalledBy ilCourseImportLinkGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportLinkGUI: ilExerciseHandlerGUI
 */

class ilCourseImportLinkGUI{
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

    public function __construct()
    {
        global  $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
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

        $this->ctrl->setParameterByClass('ilexercisehandlergui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportlinkgui', 'ref_id', $_GET['ref_id']);

        $this->tabs->addTab('link', $this->pl->txt('tab_link'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportLinkGUI')));

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilexercisehandlergui',
            'ilexercisehandlergui',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }
    protected function setTitleAndIcon()
    {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->lng->txt('obj_crss'));
        $this->tpl->setDescription($this->lng->txt('obj_crss_desc'));
    }
    public function executeCommand()
    {
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();
var_dump($cmd);
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

    protected function initForm()
    {

        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('link_exercise'));
        $data = $this->getGroups($_GET['ref_id']);

        foreach ($data as $row){


            $checkbox_link = new ilCheckboxInputGUI($row['title'], $row['obj_id']);
           // $checkbox_link->setValue($this->isReferenced($row['obj_id'],$_GET['ref_id']));
            $form->addItem($checkbox_link);



        }
        $form->addCommandButton('link',$this->pl->txt('save_link'));
        $form->addCommandButton('bla',"bla");
        return $form;
    }

    protected function isReferenced($group_id,$exercise_id){

    }
    protected function getAdminFolderIds(){
        $ids = array();


        //TODO: get Ref_IDS of AdminFolders in Groups.
        //temporarilly hard coded
        $ids = [108,109,110];

        return $ids;
    }

    protected function saveLink()
    {
        global $rbacreview, $log, $tree, $ilObjDataCache, $ilUser;

        $this->dosomethincrazy();

            $linked_to_folders = array();

            include_once "Services/AccessControl/classes/class.ilRbacLog.php";
            $rbac_log_active = ilRbacLog::isActive();

            $group_admin_folder_ids = $this->getAdminFolderIds();

            foreach($group_admin_folder_ids as $folder_ref_id)
            {
                $linked_to_folders[] = $ilObjDataCache->lookupTitle($ilObjDataCache->lookupObjId($folder_ref_id));

                //get Ref_id of Excercise you want to link
                $ref_id = $_GET['ref_id'];

                    // get node data
                    $top_node = $tree->getNodeData($ref_id);

                    // get subnodes of top nodes
                    $subnodes[$ref_id] = $tree->getSubtree($top_node);


                // now move all subtrees to new location
                foreach($subnodes as $key => $subnode)
                {
                    // first paste top_node....
                    $obj_data = ilObjectFactory::getInstanceByRefId($key);
                    $new_ref_id = $obj_data->createReference();
                    $obj_data->putInTree($folder_ref_id);
                    $obj_data->setPermissions($folder_ref_id);

                    // rbac log
                    if($rbac_log_active)
                    {
                        $rbac_log_roles = $rbacreview->getParentRoleIds($new_ref_id, false);
                        $rbac_log = ilRbacLog::gatherFaPa($new_ref_id, array_keys($rbac_log_roles), true);
                        ilRbacLog::add(ilRbacLog::LINK_OBJECT, $new_ref_id, $rbac_log, $key);
                    }

                    // BEGIN ChangeEvent: Record link event.
                    $node_data = $tree->getNodeData($new_ref_id);
                    ilChangeEvent::_recordWriteEvent($node_data['obj_id'], $ilUser->getId(), 'add',
                        $ilObjDataCache->lookupObjId($folder_ref_id));
                    ilChangeEvent::_catchupWriteEvents($node_data['obj_id'], $ilUser->getId());
                    // END PATCH ChangeEvent: Record link event.
                }

                $log->write(__METHOD__.', link finished');


            ilUtil::sendSuccess(sprintf($this->lng->txt('mgs_objects_linked_to_the_following_folders'), implode(', ', $linked_to_folders)), true);
        } // END LINK
    }


    protected function getGroups(){
        global $ilDB;

        $data = array();
        $query = "select od.title, od.obj_id
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id 
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '83'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        return $data;
    }

    protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}
