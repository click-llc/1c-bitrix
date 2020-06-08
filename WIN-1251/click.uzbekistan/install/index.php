<?php

// Localize file.
IncludeModuleLangFile(__FILE__);

/**
 * QIWI payment gateway module installer.
 */
class click_uzbekistan extends CModule
{
    /** @var string The module ID. */
    public $MODULE_ID = 'click.uzbekistan';

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Get version.
        require __DIR__.DIRECTORY_SEPARATOR.'version.php';
        /** @var array $arModuleVersion Module version metadata. */

        $this->MODULE_NAME         = GetMessage('CLICK_UZ_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = GetMessage('CLICK_UZ_MODULE_DESCRIPTION');
        $this->PARTNER_NAME        = GetMessage('CLICK_UZ_PARTNER_NAME');
        $this->PARTNER_URI         = GetMessage('CLICK_UZ_PARTNER_URI');
        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    /**
     * Install module.
     */
    public function DoInstall()
    {
        global $DB;
        $DB->Query("
			CREATE TABLE IF NOT EXISTS `click_transactions` (
					`ID` BIGINT(20)	UNSIGNED NOT NULL AUTO_INCREMENT,			
                    `click_trans_id` BIGINT(20) UNSIGNED NOT NULL,
                    `click_paydoc_id` BIGINT(20) UNSIGNED NOT NULL,
                    `service_id` BIGINT(20) UNSIGNED NOT NULL,                    
                    `merchant_trans_id` BIGINT(20) UNSIGNED NOT NULL,                    
                    `amount`  DECIMAL(20, 2) NOT NULL,
                    `error` BIGINT(20) UNSIGNED NOT NULL,
                    `error_note` NVARCHAR(120),
                    `action` tinyint(2),
                    PRIMARY KEY (`ID`)
                ) ENGINE=InnoDB;");

        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
        COption::SetOptionInt($this->MODULE_ID, 'delete', false);
    }

    /**
     * Recursive copy files to directory.
     *
     * @param  string  $from  Source path.
     * @param  string  $to  Distance directory path.
     */
    protected function copyRecursive($from, $to)
    {
        if (file_exists($from)) {
            if (! file_exists($to)) {
                mkdir($to, fileperms($from), true);
            }
            if (is_dir($to)) {
                if (is_dir($from)) {
                    /** @var RecursiveDirectoryIterator $files */
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($files as $file) {
                        $fromPath = $from.'/'.$files->getSubPathName();
                        $toPath = $to.'/'.$files->getSubPathName();
                        if ($file->isDir()) {
                            mkdir($toPath, fileperms($fromPath), true);
                        } elseif ($file->isFile() || $file->isLink()) {
                            copy($fromPath, $toPath);
                        }
                    }
                } elseif (is_file($from) || is_link($from)) {
                    $toPath = $to.'/'.basename($from);
                    copy($from, $toPath);
                }
            }
        }
    }

    /**
     * Install module files.
     */
    public function InstallFiles()
    {
        $this->copyRecursive(
            __DIR__ . '/click_uzbekistan',
            dirname(__FILE__, 4).'/php_interface/include/sale_payment/click_uzbekistan'
        );
        $this->copyRecursive(__DIR__.'/images', dirname(__FILE__, 5).'/bitrix/images/sale/sale_payments');
    }

    /**
     * Uninstall module.
     */
    public function DoUninstall()
    {
        COption::SetOptionInt($this->MODULE_ID, 'delete', true);
        UnRegisterModule($this->MODULE_ID);
        $this->UnInstallFiles();
    }

    /**
     * Recursive delete files and dirs.
     *
     * @param  string  $path
     * @param  bool  $onlyIfEmpty  Apply on empty dirs only.
     *
     * @return void
     */
    protected function deleteRecursive($path, $onlyIfEmpty = false)
    {
        if (file_exists($path)) {
            if (is_dir($path)) {
                /** @var DirectoryIterator $files */
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                if (iterator_count($files) === 0 && $onlyIfEmpty) {
                    rmdir($path);
                } elseif (! $onlyIfEmpty) {
                    foreach ($files as $file) {
                        $filePath = $file->getPathname();
                        if ($file->isDir()) {
                            rmdir($filePath);
                        } elseif ($file->isFile() || $file->isLink()) {
                            unlink($filePath);
                        }
                    }
                    unset($file);
                    rmdir($path);
                }
                unset($files);
            } elseif (is_file($path) || is_link($path)) {
                unlink($path);
            }
            if (! file_exists($path)) {
                $parentPath = dirname($path);
                $this->deleteRecursive($parentPath, true);
            }
        }
    }

    /**
     * Uninstall module files.
     */
    public function UnInstallFiles()
    {
        $this->deleteRecursive(
            dirname(__FILE__, 4).'/php_interface/include/sale_payment/click_uzbekistan'
        );
        foreach (new DirectoryIterator(__DIR__.'/images') as $file) {
            if ($file->isFile()) {
                $this->deleteRecursive(
                    dirname(__FILE__, 5).'/bitrix/images/sale/sale_payments/'.$file->getBasename()
                );
            }
        }


    }
}
