<?php
namespace WEBprofil\WpFalcleaner\Controller;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WEBprofil\WpFalcleaner\Domain\Model\Log;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileRepository;
use WEBprofil\WpFalcleaner\Domain\Repository\CleanRepository;
use WEBprofil\WpFalcleaner\Domain\Repository\LogRepository;

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

/**
 * CleanController
 */
class CleanController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public function __construct(CleanRepository $cleanRepository, FileRepository $fileRepository, LogRepository $logRepository)
    {
        $this->cleanRepository = $cleanRepository;
        $this->fileRepository = $fileRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $duplicates = $this->cleanRepository->findDuplicates();
        $this->view->assign('duplicates', $duplicates);
    }

    /**
     * action duplicate
     *
     * @return void
     */
    public function duplicateAction()
    {
        $duplicates = $this->cleanRepository->findDuplicates();
        $this->view->assign('duplicates', $duplicates);
    }

    /**
     * action duplicateDelete
     *
     * @return void
     */
    public function duplicateDeleteAction()
    {
        $duplicates = $this->cleanRepository->findDuplicates();
        $arguments = $this->request->getArguments();
        $types = $arguments['types'];
        $names = $arguments['names'];
        $cleanupFiles = [];
        $rules = $this->generateRuleSet($types, $names);
        foreach ($duplicates as $duplicate) {
            $sha1 = $duplicate['sha1'];
            $files = [
                'duplicateFiles' => [],
                'deleteFiles' => [],
                'keepFiles' => []
            ];
            $files['duplicateFiles'] = $this->cleanRepository->findBySha1($sha1);
            foreach ($rules as $rule) {
                switch ($rule['type']) {
                    case 'keepnew':
                        $files = $this->renderRuleKeepnew($files);
                        break;
                    case 'keepold':
                        $files = $this->renderRuleKeepold($files);
                        break;
                    case 'keepfolder':
                        $files = $this->renderRuleKeepfolder($files, $rule['name']);
                        break;
                    case 'deletefolder':
                        $files = $this->renderRuleDeletefolder($files, $rule['name']);
                        break;
                    case 'deletereference':
                        $files = $this->renderRuleDeletereference($files);
                        break;
                    case 'deletefilename':
                        $files = $this->renderRuleDeletefilename($files);
                        break;
                }
            }
            $files = $this->findKeepfiles($files);
            $cleanupFiles[] = $files;
        }

        // view
        $this->view->assign('cleanupFiles', $cleanupFiles);

        // delete only, if button pressed
        if (!empty($arguments['delete'])) {
            $this->deleteFiles($cleanupFiles);
        }
    }

    /**
     * findKeepfiles
     *
     * @return void
     */
    private function findKeepfiles($files)
    {
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $duplicateFile) {
                $foundInDeleteFiles = false;
                if (is_array($files['deleteFiles'])) {
                    foreach ($files['deleteFiles'] as $deleteFile) {
                        if ($duplicateFile['uid'] == $deleteFile->getUid()) {
                            $foundInDeleteFiles = true;
                        }
                    }
                }
                if ($foundInDeleteFiles == false) {
                    $files['keepFiles'][] = $this->fileRepository->findByUid($duplicateFile['uid']);
                }
            }
        }
        return $files;
    }

    /**
     * deleteFiles
     *
     * @return void
     */
    private function deleteFiles($cleanupFiles)
    {
        if (is_array($cleanupFiles)) {
            foreach ($cleanupFiles as $cleanupFile) {
                if (is_array($cleanupFile['deleteFiles'])) {
                    $keepFile = $this->findMostReferencedFile($cleanupFile['keepFiles']);

                    // switch to best metadata:
                    $bestMetadata = $this->findBestMetadata($cleanupFile['deleteFiles']);
                    $this->switchMetadata($bestMetadata, $keepFile);

                    // delete duplicate files:
                    foreach ($cleanupFile['deleteFiles'] as $deleteFile) {
                        $filename = Environment::getPublicPath() . '/' . $deleteFile->getPublicUrl();
                        $switches = 0;
                        $references = $this->cleanRepository->countRefIndex($deleteFile)[0]['count'];

                        // switch references from the deleted file to the keepfile:
                        $switches += $this->switchSysFileReference($deleteFile, $keepFile);
                        $switches += $this->switchRefindex($deleteFile, $keepFile);

                        // remove sys_files entry:
                        if ($switches == $references) { // delete file only, if so many files are switched, as references exists
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
                            $affectedRows = $queryBuilder
                                ->delete('sys_file')
                                ->where(
                                    $queryBuilder->expr()->eq('uid', (int)$deleteFile->getUid())
                                )
                                ->execute();
                            // delete physically only if deletefile is not the same as keepfile (multiple sys_file entries)
                            if ($deleteFile->getIdentifier() != $keepFile->getIdentifier()) {
                                @unlink($filename);
                            }
                        }

                        // write Log:
                        $log = $this->objectManager->get(Log::class);
                        $log->setFilename($filename);
                        $log->setReason(1);
                        $this->logRepository->add($log);
                    }
                }
            }
        }
    }

    /**
     * switchSysFileReference
     *
     * @return void
     */
    private function switchSysFileReference($deleteFile, $keepFile)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $affectedRows = $queryBuilder
            ->update('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_local', (int)$deleteFile->getUid())
            )
            ->set('uid_local', (int)$keepFile->getUid())
            ->execute();
        return $affectedRows;
    }

    /**
     * switchRteHtmlarea
     *
     * @return void
     */
    private function switchRefindex($deleteFile, $keepFile)
    {
        $count = 0;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $statement = $queryBuilder
            ->select('tablename', 'field')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_uid', $deleteFile->getUid()),
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('sys_file')),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter('sys_file_metadata')),
                $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter('sys_file_reference'))
            )
            ->execute();
        while ($refindex = $statement->fetch()) {
            // <LINK file:xxx>
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($refindex['tablename']);
            $affectedRows = $queryBuilder
                ->update($refindex['tablename'])
                ->where(
                    $queryBuilder->expr()->like($refindex['field'], "%<LINK file:" . (int)$deleteFile->getUid() . ">%")
                )
                ->set($refindex['field'], $queryBuilder->createNamedParameter("REPLACE(" . $refindex['field'] . ", '<LINK file:" . $deleteFile->getUid() . ">', '<LINK file:" . $keepFile->getUid() . ">')"))
                ->execute();
            $count += $affectedRows;

            // data-htmlarea-file-uid="xxx"
            // src="xxx.xxx"
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($refindex['tablename']);
            $affectedRows = $queryBuilder
                ->update($refindex['tablename'])
                ->where(
                    $queryBuilder->expr()->like($refindex['field'], "%data-htmlarea-file-uid=\"" . (int)$deleteFile->getUid() . "\"%")
                )
                ->set($refindex['field'], $queryBuilder->createNamedParameter("REPLACE(" . $refindex['field'] . ", 'data-htmlarea-file-uid=\"" . $deleteFile->getUid() . "\"', 'data-htmlarea-file-uid=\"" . $keepFile->getUid() . "\"')"))
                ->execute();
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($refindex['tablename']);
            $affectedRows2 = $queryBuilder
                ->update($refindex['tablename'])
                ->where(
                    $queryBuilder->expr()->like($refindex['field'], "%src=\"" . $deleteFile->getPublicUrl() . "\"%")
                )
                ->set($refindex['field'], $queryBuilder->createNamedParameter("REPLACE(" . $refindex['field'] . ", 'src=\"" . $deleteFile->getPublicUrl() . "\"', 'src=\"" . $keepFile->getPublicUrl() . "\"')"))
                ->execute();
            $count += $affectedRows;

            // t3://file?uid=xxx
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($refindex['tablename']);
            $affectedRows = $queryBuilder
                ->update($refindex['tablename'])
                ->where(
                    $queryBuilder->expr()->like($refindex['field'], "t3://file?uid=" . $deleteFile->getUid())
                )
                ->set($refindex['field'], $queryBuilder->createNamedParameter("REPLACE(" . $refindex['field'] . ", 't3://file?uid=" . $deleteFile->getUid() . "', 't3://file?uid=" . $keepFile->getUid() . "')"))
                ->execute();
            $count += $affectedRows;

            // file:xxx
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($refindex['tablename']);
            $affectedRows = $queryBuilder
                ->update($refindex['tablename'])
                ->where(
                    $queryBuilder->expr()->like($refindex['field'], "file:" . $deleteFile->getUid())
                )
                ->set($refindex['field'], $queryBuilder->createNamedParameter("REPLACE(" . $refindex['field'] . ", 'file:" . $deleteFile->getUid() . "', 'file:" . $keepFile->getUid() . "')"))
                ->execute();
            $count += $affectedRows;
        }
        return $count;
    }

    /**
     * switchMetadata
     *
     * @return void
     */
    private function findBestMetadata($deleteFiles)
    {
        foreach ($deleteFiles as $key => $deleteFile) {

        }
        return $deleteFiles[0];
        // @todo: write function and respect languages!
    }

    /**
     * switchMetadata
     *
     * @return void
     */
    private function switchMetadata($bestMetadata, $keepFile)
    {
        // @todo: write function
    }

    /**
     * findMostReferencedFile
     *
     * @return void
     */
    private function findMostReferencedFile($keepFiles)
    {
        $maxReferences = 0;
        $most = 0;
        foreach ($keepFiles as $key => $keepFile) {
            $references = $this->cleanRepository->countRefIndex($keepFile);
            if ($references > $maxReferences) {
                $most = $key;
            }
        }
        return $keepFiles[$most];
    }

    /**
     * renderRuleKeepnew
     *
     * @return void
     */
    private function renderRuleKeepnew($files)
    {
        $maxModificationDate = 0;
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                if ($duplicateFile['modification_date'] > $maxModificationDate) {
                    $keepKey = $key;
                    $maxModificationDate = $duplicateFile['modification_date'];
                }
            }
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                if ($keepKey != $key) {
                    if (!$this->checkIfLastFile($files['duplicateFiles'], $files['deleteFiles'])) {
                        $files['deleteFiles'][] = $this->fileRepository->findByUid($duplicateFile['uid']);
                    }
                }
            }
        }
        return $files;
    }

    /**
     * renderRuleKeepold
     *
     * @return void
     */
    private function renderRuleKeepold($files)
    {
        $minModificationDate = 9999999999;
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                if ($duplicateFile['modification_date'] < $minModificationDate) {
                    $keepKey = $key;
                    $minModificationDate = $duplicateFile['modification_date'];
                }
            }
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                if ($keepKey != $key) {
                    if (!$this->checkIfLastFile($files['duplicateFiles'], $files['deleteFiles'])) {
                        $files['deleteFiles'][] = $this->fileRepository->findByUid($duplicateFile['uid']);
                    }
                }
            }
        }
        return $files;
    }

    /**
     * renderRuleKeepfolder
     *
     * @return void
     */
    private function renderRuleKeepfolder($files, $folder)
    {
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                $file = $this->fileRepository->findByUid($duplicateFile['uid']);
                if (!substr($file->getPublicUrl(), 0, strlen($folder)) === $folder) {
                    if (!$this->checkIfLastFile($files['duplicateFiles'], $files['deleteFiles'])) {
                        $files['deleteFiles'][] = $this->fileRepository->findByUid($duplicateFile['uid']);
                    }
                }
            }
        }
        return $files;
    }

    /**
     * renderRuleDeletefolder
     *
     * @return void
     */
    private function renderRuleDeletefolder($files, $folder)
    {
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                $file = $this->fileRepository->findByUid($duplicateFile['uid']);
                if (substr($file->getPublicUrl(), 0, strlen($folder)) === $folder) {
                    if (!$this->checkIfLastFile($files['duplicateFiles'], $files['deleteFiles'])) {
                        $files['deleteFiles'][] = $this->fileRepository->findByUid($duplicateFile['uid']);
                    }
                }
            }
        }
        return $files;
    }

    /**
     * renderRuleDeletereference
     *
     * @return void
     */
    private function renderRuleDeletereference($files)
    {
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                $file = $this->fileRepository->findByUid($duplicateFile['uid']);
                $references = $this->cleanRepository->countRefIndex($file);
                $referencesCount = $references[0]['count'];
                if ($referencesCount == 0) {
                    if (!$this->checkIfLastFile($files['duplicateFiles'], $files['deleteFiles'])) {
                        $files['deleteFiles'][] = $file;
                    }
                }
            }
        }
        return $files;
    }

    /**
     * renderRuleDeletefilename
     *
     * @return void
     */
    private function renderRuleDeletefilename($files)
    {
        if (is_array($files['duplicateFiles'])) {
            foreach ($files['duplicateFiles'] as $key => $duplicateFile) {
                $identifier = $duplicateFile['identifier'];
                $fileDotParts = explode('.', $identifier);
                $fileDotCount = count($fileDotParts);
                $fileName = $fileDotParts[$fileDotCount-2];
                $fileNameNumber = substr($fileName, -2) + 0;
                $fileNameUnderline = substr($fileName, -3, 1);
                if ($fileNameNumber > 0 && $fileNameUnderline == '_') {
                    if (!$this->checkIfLastFile($files['duplicateFiles'], $files['deleteFiles'])) {
                        $files['deleteFiles'][] = $this->fileRepository->findByUid($duplicateFile['uid']);
                    }
                }
            }
        }
        return $files;
    }

    /**
     * checkIfLastFile
     *
     * @return void
     */
    private function checkIfLastFile($duplicateFiles, $deleteFiles)
    {
        $countDuplicateFiles = count($duplicateFiles);
        $countDeleteFiles = count($deleteFiles);
        if ( ($countDuplicateFiles-1) > $countDeleteFiles) { // don't delete all files. keep one
            return false;
        } else {
            return true;
        }
    }

    /**
     * generateRuleSet
     *
     * @return void
     */
    private function generateRuleSet($types, $names)
    {
        if (is_array($types) && count($types) > 0) {
            foreach ($types as $key => $type) {
                $rules[$key]['type'] = $type;
                $rules[$key]['name'] = $names[$key];
            }
        } else {
            // Defaultrule:
            $rules[1]['type'] = 'keepnew';
        }
        return $rules;
    }

}
