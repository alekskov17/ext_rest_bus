<? defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__file__);

if (class_exists('krayt_apprest')) {
    return;
}

class krayt_apprest extends CModule
{
    var $MODULE_ID = "krayt.apprest";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function __construct()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __file__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include ($path . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("SCOM_INSTALL_NAME_krayt.apprest");
        $this->MODULE_DESCRIPTION = Loc::getMessage("SCOM_INSTALL_DESCRIPTION_krayt.apprest");
        $this->PARTNER_NAME = Loc::getMessage("SPER_PARTNER_krayt.apprest");
        $this->PARTNER_URI = Loc::getMessage("PARTNER_URI_krayt.apprest");

    }

    function InstallDB($arParams = array())
    {

        //#SET_MORE#
        return true;
    }

     function UnInstallDB($arParams = array())
    {

        return true;
    }
    function InstallFiles($arParams = array())
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("rest", "OnRestServiceBuildDescription", self::$MODULE_ID, "\Krayt\Apprest\RestMobile", "OnRestServiceBuildDescription");
        return true;
    }


     function UnInstallFiles()
    {

        return true;
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule('krayt.apprest');

    }

    function DoUninstall()
    {
        global $APPLICATION;
        UnRegisterModule('krayt.apprest');
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
}
?>