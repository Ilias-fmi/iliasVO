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
		if ($a_part == 'tabs' && ilObject::_lookupType($_GET['ref_id'], true) == 'crss') {
			/** @var ilTabsGUI $tabs */
			$tabs = $a_par['tabs'];

            $this->ctrl->setParameterByClass('ilcourseimportgui', 'ref_id', $_GET['ref_id']);
            $link = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGUI'));

            $this->ctrl->setParameterByClass('ilcourseimportgroupgui', 'ref_id', $_GET['ref_id']);
            $link1 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI'));

            $this->ctrl->setParameterByClass('ilcourseimportgrouptablegui', 'ref_id', $_GET['ref_id']);
            $link2 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupTableGUI'));

			$tabs->addTab('course_import', $this->pl->txt('tab_course_import'), $link);

            $tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $link1);

            $tabs->addTab('course_management_tbl', $this->pl->txt('tab_course_management_tbl'), $link2);

        }

	}

}