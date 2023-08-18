<?php
// Импорт необходимых классов Bitrix
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Main\UserField\Types\FileType;
use Bitrix\Main\UserField\Types\HTMLType;

// Создание пользовательского типа свойства
class CustomUserType extends \CUserTypeString
{
    const USER_TYPE_ID = 'custom_property'; // Уникальный идентификатор пользовательского типа свойства

    // Описание пользовательского типа свойства
    public static function GetUserTypeDescription()
    {
        return array(
            'USER_TYPE_ID' => self::USER_TYPE_ID,
            'CLASS_NAME' => __CLASS__, // Имя текущего класса
            'DESCRIPTION' => 'Файл + Строка + HTML/Text', // Описание пользовательского типа
            'BASE_TYPE' => StringType::USER_TYPE_ID, // Базовый тип данных (в данном случае, строка)
        );
    }

    // Определение типа колонки в базе данных в зависимости от используемой СУБД
    public static function GetDBColumnType($userField)
    {
        global $DB;
        switch (strtolower($DB->type)) {
            case 'mysql':
                return 'text';
            case 'oracle':
                return 'varchar2(2000 char)';
            case 'mssql':
                return 'varchar(2000)';
        }
        return 'text';
    }

    // Отображение поля редактирования в административной части
    public static function GetEditFormHTML($userField, $htmlControl)
    {
        $value = htmlspecialcharsbx($htmlControl['VALUE']); // Значение свойства, экранированное для вывода
        ob_start(); // Начало буферизации вывода
?>
        <tr>
            <td>
                <input type="text" name="<?= $htmlControl['NAME'] ?>" value="<?= $value ?>" size="50">
            </td>
        </tr>
    <?php
        return ob_get_clean(); // Завершение буферизации и возврат содержимого
    }

    // Отображение значения в административном списке
    public static function GetAdminListViewHTML($userField, $htmlControl)
    {
        $value = htmlspecialcharsbx($htmlControl['VALUE']); // Значение свойства, экранированное для вывода
        return $value;
    }

    // Отображение поля редактирования в административном списке
    public static function GetAdminListEditHTML($userField, $htmlControl)
    {
        $value = htmlspecialcharsbx($htmlControl['VALUE']); // Значение свойства, экранированное для вывода
        return '<input type="text" name="' . $htmlControl['NAME'] . '" value="' . $value . '" size="20">';
    }

    // Отображение поля редактирования множественных свойств в административной части
    public static function GetEditFormHTMLMulty($userField, $htmlControl)
    {
        ob_start(); // Начало буферизации вывода
        $value = '';
        if (is_array($htmlControl['VALUE'])) {
            $value = $htmlControl['VALUE']['TEXT'];
        }
    ?>
        <tr>
            <td>
                <input type="text" name="<?= $htmlControl['NAME'] ?>[TEXT]" value="<?= htmlspecialcharsbx($value) ?>" size="50">
            </td>
        </tr>
    <?php
        return ob_get_clean(); // Завершение буферизации и возврат содержимого
    }
    
    // Дополнительные методы
    public static function GetAdminListViewHTMLMulty($userField, $htmlControl)
    {
        $value = '';
        if (is_array($htmlControl['VALUE'])) {
            $value = $htmlControl['VALUE']['TEXT'];
        }
        return htmlspecialcharsbx($value);
    }

    public static function GetAdminListEditHTMLMulty($userField, $htmlControl)
    {
        $value = '';
        if (is_array($htmlControl['VALUE'])) {
            $value = $htmlControl['VALUE']['TEXT'];
        }
        return '<input type="text" name="' . $htmlControl['NAME'] . '[TEXT]" value="' . htmlspecialcharsbx($value) . '" size="20">';
    }

    public static function GetFilterHTML($userField, $htmlControl)
    {
        return '&nbsp;';
    }

    public static function OnBeforeSave($userField, $value)
    {
        if (is_array($value) && isset($value['TEXT'])) {
            return $value['TEXT'];
        }
        return $value;
    }

    public static function GetEditFormHTMLMulty($userField, $htmlControl)
    {
        $value = '';
        $attachedFileId = 0;
        if (is_array($htmlControl['VALUE'])) {
            $value = $htmlControl['VALUE']['TEXT'];
            $attachedFileId = intval($htmlControl['VALUE']['FILE']);
        }
        ob_start();
    ?>
        <tr>
            <td>
                <textarea name="<?= $htmlControl['NAME'] ?>[TEXT]" cols="40" rows="5"><?= htmlspecialcharsbx($value) ?></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <?php
                $attachedFileInputName = $htmlControl['NAME'] . '[FILE]';
                if ($attachedFileId > 0) {
                    echo CFileInput::Show(
                        $attachedFileInputName,
                        $attachedFileId,
                        array(
                            'IMAGE' => 'N', 'PATH' => 'Y', 'FILE_SIZE' => 'Y',
                            'DIMENSIONS' => 'Y', 'IMAGE_POPUP' => 'Y', 'MAX_SIZE' => array('W' => 50, 'H' => 50),
                        )
                    );
                } else {
                    echo CFileInput::Show(
                        $attachedFileInputName,
                        '',
                        array(
                            'IMAGE' => 'N', 'PATH' => 'Y', 'FILE_SIZE' => 'Y',
                            'DIMENSIONS' => 'Y', 'IMAGE_POPUP' => 'Y', 'MAX_SIZE' => array('W' => 50, 'H' => 50),
                        )
                    );
                }
                ?>
            </td>
        </tr>
    <?php
        return ob_get_clean();
    }

    public static function OnBeforeSaveMulty($userField, $value)
    {
        $result = array();
        if (is_array($value) && isset($value['TEXT'])) {
            $result['TEXT'] = $value['TEXT'];
        }
        if (is_array($value) && isset($value['FILE']) && intval($value['FILE']) > 0) {
            $result['FILE'] = intval($value['FILE']);
        }
        return $result;
    }

    public static function GetAdminListViewHTMLMulty($userField, $htmlControl)
    {
        $value = '';
        $attachedFileId = 0;
        if (is_array($htmlControl['VALUE'])) {
            $value = $htmlControl['VALUE']['TEXT'];
            $attachedFileId = intval($htmlControl['VALUE']['FILE']);
        }
        $output = htmlspecialcharsbx($value);
        if ($attachedFileId > 0) {
            $attachedFileData = CFile::GetFileArray($attachedFileId);
            if ($attachedFileData) {
                $attachedFileUrl = $attachedFileData['SRC'];
                $output .= "<br><a href=\"$attachedFileUrl\" target=\"_blank\">Download File</a>";
            }
        }
        return $output;
    }

    public static function GetAdminListEditHTMLMulty($userField, $htmlControl)
    {
        $value = '';
        $attachedFileId = 0;
        if (is_array($htmlControl['VALUE'])) {
            $value = $htmlControl['VALUE']['TEXT'];
            $attachedFileId = intval($htmlControl['VALUE']['FILE']);
        }
        ob_start();
    ?>
        <textarea name="<?= $htmlControl['NAME'] ?>[TEXT]" cols="20" rows="5"><?= htmlspecialcharsbx($value) ?></textarea>
        <br>
        <?php
        $attachedFileInputName = $htmlControl['NAME'] . '[FILE]';
        if ($attachedFileId > 0) {
            echo CFileInput::Show(
                $attachedFileInputName,
                $attachedFileId,
                array(
                    'IMAGE' => 'N', 'PATH' => 'Y', 'FILE_SIZE' => 'Y',
                    'DIMENSIONS' => 'Y', 'IMAGE_POPUP' => 'Y', 'MAX_SIZE' => array('W' => 50, 'H' => 50),
                )
            );
        } else {
            echo CFileInput::Show(
                $attachedFileInputName,
                '',
                array(
                    'IMAGE' => 'N', 'PATH' => 'Y', 'FILE_SIZE' => 'Y',
                    'DIMENSIONS' => 'Y', 'IMAGE_POPUP' => 'Y', 'MAX_SIZE' => array('W' => 50, 'H' => 50),
                )
            );
        }
        ?>
<?php
        return ob_get_clean();
    }
}

// Регистрация обработчика пользовательских типов свойств
AddEventHandler('main', 'OnUserTypeBuildList', array('CustomUserType', 'GetUserTypeDescription'));
