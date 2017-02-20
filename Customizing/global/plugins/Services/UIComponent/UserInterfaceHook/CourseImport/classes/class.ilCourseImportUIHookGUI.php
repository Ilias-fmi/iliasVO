<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
/**
 * Class ilCourseImportUIHookGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilCourseImportUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilCourseImportPlugin
	 */
	protected $pl;

	public function __construct()
	{
		global $ilCtrl;
		$this->ctrl = $ilCtrl;
		$this->pl = ilCourseImportPlugin::getInstance();
	}


	function getHTML($a_comp, $a_part, $a_par = array())
	{

	}

	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
        /** @var ilTabsGUI $tabs */
        global $ilTabs;
        
        global $ilAccess, $ilErr;

        $tabs = $a_par['tabs'];

        // Tab im einzelnen Kurs
        if (($_GET["baseClass"] == 'ilRepositoryGUI' || $_GET["baseClass"] == 'ilrepositorygui') && $a_part == 'tabs' && ilObject::_lookupType($_GET['ref_id'], true) == 'crs'){

            $this->ctrl->setParameterByClass('ilcourseimportgroupgui', 'ref_id', $_GET['ref_id']);
            $link1 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI'));
            $tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $link1);

        }

        // Tab in einer Uebung
        if (($_GET["baseClass"] == 'ilExerciseHandlerGUI' || $_GET["baseClass"] == 'ilexercisehandlergui') && $a_part == 'tabs'&&$ilAccess->checkAccess("write", "", $_GET['ref_id'])){
            $this->ctrl->setParameterByClass('ilcourseimportlinkgui', 'ref_id', $_GET['ref_id']);
            $this->ctrl->setParameterByClass('ilcourseimporttutorgui','ref_id',$_GET['ref_id']);
            $link2 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportLinkGUI'));
            $link3 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCourseImportTutorGUI'));
            $tabs->addTab('link', $this->pl->txt('tab_link'), $link2);
            $tabs->addTab('tutor',$this->pl->txt('tab_tutor'),$link3);
        }

        //Tab in Administration -> Kurs
        if ($a_part == 'tabs'&& ilObject::_lookupType($_GET['ref_id'], true) == 'crss'){

            $this->ctrl->setParameterByClass('ilcourseimportgui', 'ref_id', $_GET['ref_id']);
            $link = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGUI'));
            $tabs->addTab('course_import', $this->pl->txt('tab_course_import'), $link);

        }

	}

}