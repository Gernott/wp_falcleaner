<?php
namespace WEBprofil\WpFalcleaner\Domain\Repository;

/***
 *
 * This file is part of the "FAL cleaner" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Gernot Ploiner <gp@webprofil.at>, WEBprofil - Gernot Ploiner e.U.
 *
 ***/

use TYPO3\CMS\Core\Resource\File;

/**
 * The repository for Clean
 */
class CleanRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function findDuplicates()
    {
        $query = $this->createQuery();
        $sql = 'SELECT sha1, name, size, count(sha1) AS count FROM sys_file WHERE missing = 0 GROUP BY sha1 HAVING count > 1';
        $query->statement($sql);
        return $query->execute(true);
    }

    public function findBySha1($sha1)
    {
        $query = $this->createQuery();
        $sql = 'SELECT * FROM sys_file WHERE missing = 0 AND sha1 = "' . $sha1 . '";';
        $query->statement($sql);
        return $query->execute(true);
    }

    public function countRefIndex(File $file)
    {
        $query = $this->createQuery();
        $sql = "SELECT count(ref_uid) AS count FROM sys_refindex WHERE ref_table = 'sys_file' AND ref_uid = " . $file->getUid() . " AND deleted = 0 AND tablename != 'sys_file_metadata'";
        $query->statement($sql);
        return $query->execute(true);
    }

}
