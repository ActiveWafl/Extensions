<?php
\Wafl\Extensions\Forums\Forums::$TablePrefix = "";
\Wafl\Extensions\Forums\Forums::Set_DatabaseInstallScripts(array(__DIR__."/CreateTables.sql"));
\Wafl\Extensions\Forums\Forums::Set_LanguageFileClassname("Wafl\Extensions\Forums\Conf\Lang\en_us");
?>