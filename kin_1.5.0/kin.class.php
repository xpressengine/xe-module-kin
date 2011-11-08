<?php
    /**
     * @class  kin
     * @author zero (skklove@gmail.com)
     * @brief  kin high class
     **/

    class kin extends ModuleObject {

        var $search_option = array('title','content','title_content','comment','user_name','nick_name','user_id','tag');
		var $list_count = 20;
		var $page_count = 10;

        function moduleInstall() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $oModuleController->insertTrigger('member.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after');
            return new Object();
        }

        function checkUpdate() {
        	$oModuleModel = &getModel('module');

            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after')) {
            	return true;
            }
            return false;
        }

        function moduleUpdate() {
        	$oModuleController = &getController('module');
        	$oModuleController->insertTrigger('member.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after');
            return new Object(0, 'success_updated');
        }

        function recompileCache() {
        }
    }
?>
