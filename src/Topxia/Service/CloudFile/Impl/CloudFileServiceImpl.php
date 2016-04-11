<?php

namespace Topxia\Service\CloudFile\Impl;

use Topxia\Common\ArrayToolkit;
use Topxia\Service\Common\BaseService;
use Topxia\Service\CloudFile\CloudFileService;

class CloudFileServiceImpl extends BaseService implements CloudFileService
{
    public function search($conditions, $start, $limit)
    {
        $conditions['start']    = $start;
        $conditions['limit']    = $limit;
        $conditions             = $this->filterConditions($conditions);
        $result                 = $this->getCloudFileImplementor()->search($conditions);
        $createdUserIds         = ArrayToolkit::column($result['data'], 'createdUserId');
        $result['createdUsers'] = $this->getUserService()->findUsersByIds($createdUserIds);

        return $result;
    }

    protected function filterConditions($conditions)
    {
        $noArray = array();

        if (!empty($conditions['tags'])) {
            $noArray[] = $this->findGlobalIdsByTags($conditions['tags']);
        }

        if (!empty($conditions['keywords']) && in_array($conditions['searchType'], array('course', 'user'))) {
            $noArray[] = $this->findGlobalIdsByKeyWords($conditions['searchType'], $conditions['keywords']);
            unset($conditions['keywords']);
        }

        $globalIds = array();

        for ($i = 0; $i < count($noArray); $i++) {
            if (empty($noArray[$i])) {
                $globalIds = array(0);
                break;
            }

            if ($i == 0) {
                $globalIds = $noArray[$i];
            } else {
                $globalIds = array_intersect($globalIds, $noArray[$i]);
            }

            if (empty($globalIds)) {
                $globalIds = array(0);
            }
        }

        $conditions['nos'] = implode(',', $globalIds);

        $conditions = array_filter($conditions, function ($value) {
            if ($value === '0') {
                return true;
            }

            return !empty($value);
        });

        unset($conditions['searchType']);
        unset($conditions['tags']);

        return $conditions;
    }

    protected function findGlobalIdsByTags($tags)
    {
        $filesInTags = $this->getUploadFileTagService()->findByTagId($tags);
        $fileIds     = ArrayToolkit::column($filesInTags, 'fileId');
        $files       = $this->getUploadFileService()->findLocalFilesByIds($fileIds);

        if (!empty($files)) {
            return ArrayToolkit::column($files, 'globalId');
        }

        return array();
    }

    protected function findGlobalIdsByKeyWords($searchType, $keywords)
    {
        if ($searchType == 'course') {
            $courses   = $this->getCourseService()->findCoursesByLikeTitle($keywords);
            $courseIds = ArrayToolkit::column($courses, 'id');

            if (empty($courseIds)) {
                $courseIds = array('0');
            }

            $localFiles = $this->getUploadFileService()->findFilesByCourseIds($courseIds);
            $globalIds  = ArrayToolkit::column($localFiles, 'globalId');

            return $globalIds;
        } elseif ($searchType == 'user') {
            $users      = $this->getUserService()->searchUsers(array('nickname' => $keywords), array('id', 'desc'), 0, 999);
            $userIds    = ArrayToolkit::column($users, 'id');
            $localFiles = $this->getUploadFileService()->searchFiles(
                array('createdUserIds' => $userIds, 'storage' => 'cloud'), null, 0, PHP_INT_MAX
            );
            $globalIds = ArrayToolkit::column($localFiles, 'globalId');
            return $globalIds;
        }

        return array();
    }

    public function edit($globalId, $fields)
    {
        return $this->getCloudFileImplementor()->edit($globalId, $fields);
    }

    public function delete($globalId)
    {
        return $this->getCloudFileImplementor()->delete($globalId);
    }

    public function get($globalId)
    {
        return $this->getCloudFileImplementor()->get($globalId);
    }

    public function player($globalId)
    {
        return $this->getCloudFileImplementor()->player($globalId);
    }

    public function download($globalId)
    {
        return $this->getCloudFileImplementor()->download($globalId);
    }

    public function reconvert($globalId, $options = array())
    {
        return $this->getCloudFileImplementor()->reconvert($globalId, $options);
    }

    public function getDefaultHumbnails($globalId)
    {
        return $this->getCloudFileImplementor()->getDefaultHumbnails($globalId);
    }

    public function getThumbnail($globalId, $options)
    {
        return $this->getCloudFileImplementor()->getThumbnail($globalId, $options);
    }

    public function getStatistics($options = array())
    {
        return $this->getCloudFileImplementor()->getStatistics($options);
    }

    public function synData($conditions)
    {
        return $this->getCloudFileImplementor()->synData($conditions);
    }

    protected function getUploadFileService()
    {
        return $this->createService('File.UploadFileService2');
    }

    protected function getUploadFileTagService()
    {
        return $this->createService('File.UploadFileTagService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course.CourseService');
    }

    protected function getUserService()
    {
        return $this->createService('User.UserService');
    }

    protected function getCloudFileImplementor()
    {
        return $this->createService('File.CloudFileImplementor2');
    }
}