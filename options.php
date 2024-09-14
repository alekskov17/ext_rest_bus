<?php define('ADMIN_MODULE_NAME', 'krayt.apprest');

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
CModule::IncludeModule("iblock");
CModule::IncludeModule("main");
\Bitrix\Main\Loader::includeModule("catalog");
\Bitrix\Main\UI\Extension::load("ui.progressbar");
\Bitrix\Main\UI\Extension::load("ui.buttons");


$module_id = "krayt.apprest";
Loc::loadMessages(__FILE__);

global $APPLICATION, $USER, $USER_FIELD_MANAGER;

Loc::loadMessages(__FILE__);

if (!CModule::IncludeModule(ADMIN_MODULE_NAME))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
$request = Application::getInstance()->getContext()->getRequest();
$server = Application::getInstance()->getContext()->getServer();
$uriString = $request->getRequestUri();

$uri = new Uri($uriString);
$uri->addParams(['id'=> '#id#']);
$listApp = $uri->getPathQuery();
$editApp = $uri->getPathQuery();
$editApp = str_replace("%23","#",$editApp);
$addApp = str_replace("#id#","-1",$editApp);
$APPLICATION->SetTitle(Loc::getMessage('K_TITLE_INDEX_BAR'));
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>

    <div class="adm-workarea">
    <?
    if($request->get('id') > 0 || $request->get('id') == -1)
    {
        $APPLICATION->IncludeComponent(
            'bitrix:rest.hook.ap.edit',
            '',
            array(
                'EDIT_URL_TPL' => $editApp,
                'LIST_URL' => $listApp,
                'ID' => $request->get('id'),
                'SET_TITLE' => 'N',
            )
        );
    }else{
        $APPLICATION->IncludeComponent(
            'bitrix:rest.hook.ap.list',
            '',
            array(
                'EDIT_URL_TPL' => $editApp,
                'PAGE_SIZE' => 10,
            )
        );
    }

    ?>
    <a href="javascript:;" class="adm-btn adm-btn-green"
    onclick="BX.PopupMenu.show('rest_hook_menu', this, [{
    'href':'<?=$addApp?>',
    'text':'Добавить интеграцию'
    }])">
    Добавить интеграцию
    </a>
    </div>

<?require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
