<?php

declare(strict_types=1);

namespace Gitlab\Api;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Repositories extends AbstractApi
{
    public const TYPE_BRANCH = 'branch';

    public const TYPE_TAG = 'tag';

    /**
     * @param int|string $project_id
     * @param array      $parameters {
     *
     *     @var string $search
     * }
     *
     * @return mixed
     */
    public function branches($project_id, array $parameters = [])
    {
        $resolver = $this->createOptionsResolver();
        $resolver->setDefined('search')
            ->setAllowedTypes('search', 'string');

        return $this->get($this->getProjectPath($project_id, 'repository/branches'), $resolver->resolve($parameters));
    }

    /**
     * @param int|string $project_id
     * @param string     $branch
     *
     * @return mixed
     */
    public function branch($project_id, $branch)
    {
        return $this->get($this->getProjectPath($project_id, 'repository/branches/'.self::encodePath($branch)));
    }

    /**
     * @param int|string $project_id
     * @param string     $branch
     * @param string     $ref
     *
     * @return mixed
     */
    public function createBranch($project_id, $branch, $ref)
    {
        return $this->post($this->getProjectPath($project_id, 'repository/branches'), [
            'branch' => $branch,
            'ref' => $ref,
        ]);
    }

    /**
     * @param int|string $project_id
     * @param string     $branch
     *
     * @return mixed
     */
    public function deleteBranch($project_id, $branch)
    {
        return $this->delete($this->getProjectPath($project_id, 'repository/branches/'.self::encodePath($branch)));
    }

    /**
     * @param int|string $project_id
     * @param string     $branch
     * @param bool       $devPush
     * @param bool       $devMerge
     *
     * @return mixed
     */
    public function protectBranch($project_id, $branch, $devPush = false, $devMerge = false)
    {
        return $this->put($this->getProjectPath($project_id, 'repository/branches/'.self::encodePath($branch).'/protect'), [
            'developers_can_push' => $devPush,
            'developers_can_merge' => $devMerge,
        ]);
    }

    /**
     * @param int|string $project_id
     * @param string     $branch
     *
     * @return mixed
     */
    public function unprotectBranch($project_id, $branch)
    {
        return $this->put($this->getProjectPath($project_id, 'repository/branches/'.self::encodePath($branch).'/unprotect'));
    }

    /**
     * @param int|string $project_id
     * @param array      $parameters
     *
     * @return mixed
     */
    public function tags($project_id, array $parameters = [])
    {
        $resolver = $this->createOptionsResolver();

        return $this->get($this->getProjectPath($project_id, 'repository/tags'), $resolver->resolve($parameters));
    }

    /**
     * @param int|string  $project_id
     * @param string      $name
     * @param string      $ref
     * @param string|null $message
     *
     * @return mixed
     */
    public function createTag($project_id, $name, $ref, $message = null)
    {
        return $this->post($this->getProjectPath($project_id, 'repository/tags'), [
            'tag_name' => $name,
            'ref' => $ref,
            'message' => $message,
        ]);
    }

    /**
     * @param int|string $project_id
     * @param string     $tag_name
     * @param string     $description
     *
     * @return mixed
     */
    public function createRelease($project_id, $tag_name, $description)
    {
        return $this->post($this->getProjectPath($project_id, 'repository/tags/'.self::encodePath($tag_name).'/release'), [
            'id' => $project_id,
            'tag_name' => $tag_name,
            'description' => $description,
        ]);
    }

    /**
     * @param int|string $project_id
     * @param string     $tag_name
     * @param string     $description
     *
     * @return mixed
     */
    public function updateRelease($project_id, $tag_name, $description)
    {
        return $this->put($this->getProjectPath($project_id, 'repository/tags/'.self::encodePath($tag_name).'/release'), [
            'id' => $project_id,
            'tag_name' => $tag_name,
            'description' => $description,
        ]);
    }

    /**
     * @param int|string $project_id
     *
     * @return mixed
     */
    public function releases($project_id)
    {
        $resolver = $this->createOptionsResolver();

        return $this->get($this->getProjectPath($project_id, 'releases'));
    }

    /**
     * @see https://docs.gitlab.com/ee/api/commits.html#list-repository-commits
     *
     * @param int|string $project_id
     * @param array      $parameters {
     *
     *     @var string             $ref_name the name of a repository branch or tag or if not given the default branch
     *     @var \DateTimeInterface $since    only commits after or on this date will be returned
     *     @var \DateTimeInterface $until    Only commits before or on this date will be returned.
     * }
     *
     * @return mixed
     */
    public function commits($project_id, array $parameters = [])
    {
        $resolver = $this->createOptionsResolver();
        $datetimeNormalizer = function (Options $options, \DateTimeInterface $value) {
            return $value->format('c');
        };
        $booleanNormalizer = function (Options $resolver, $value) {
            return $value ? 'true' : 'false';
        };

        $resolver->setDefined('path');
        $resolver->setDefined('ref_name');
        $resolver->setDefined('since')
            ->setAllowedTypes('since', \DateTimeInterface::class)
            ->setNormalizer('since', $datetimeNormalizer)
        ;
        $resolver->setDefined('until')
            ->setAllowedTypes('until', \DateTimeInterface::class)
            ->setNormalizer('until', $datetimeNormalizer)
        ;
        $resolver->setDefined('all')
            ->setAllowedTypes('all', 'bool')
            ->setNormalizer('all', $booleanNormalizer)
        ;
        $resolver->setDefined('with_stats')
            ->setAllowedTypes('with_stats', 'bool')
            ->setNormalizer('with_stats', $booleanNormalizer)
        ;
        $resolver->setDefined('first_parent')
            ->setAllowedTypes('first_parent', 'bool')
            ->setNormalizer('first_parent', $booleanNormalizer)
        ;
        $resolver->setDefined('order')
            ->setAllowedTypes('order', ['default', 'topo'])
        ;

        return $this->get($this->getProjectPath($project_id, 'repository/commits'), $resolver->resolve($parameters));
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     *
     * @return mixed
     */
    public function commit($project_id, $sha)
    {
        return $this->get($this->getProjectPath($project_id, 'repository/commits/'.self::encodePath($sha)));
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     * @param array      $parameters
     *
     * @return mixed
     */
    public function commitRefs($project_id, $sha, array $parameters = [])
    {
        $resolver = $this->createOptionsResolver();

        return $this->get(
            $this->getProjectPath($project_id, 'repository/commits/'.self::encodePath($sha).'/refs'),
            $resolver->resolve($parameters)
        );
    }

    /**
     * @param int|string $project_id
     * @param array      $parameters {
     *
     *     @var string $branch         Name of the branch to commit into. To create a new branch, also provide start_branch.
     *     @var string $commit_message commit message
     *     @var string $start_branch   name of the branch to start the new commit from
     *     @var array $actions {
     *         @var string $action        he action to perform, create, delete, move, update
     *         @var string $file_path     full path to the file
     *         @var string $previous_path original full path to the file being moved
     *         @var string $content       File content, required for all except delete. Optional for move.
     *         @var string $encoding      text or base64. text is default.
     *     }
     *     @var string $author_email   specify the commit author's email address
     *     @var string $author_name    Specify the commit author's name.
     * }
     *
     * @return mixed
     */
    public function createCommit($project_id, array $parameters = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined('branch')
            ->setRequired('branch')
        ;
        $resolver->setDefined('commit_message')
            ->setRequired('commit_message')
        ;
        $resolver->setDefined('start_branch');
        $resolver->setDefined('actions')
            ->setRequired('actions')
            ->setAllowedTypes('actions', 'array')
            ->setAllowedValues('actions', function (array $actions) {
                return 0 < count($actions);
            })
            ->setNormalizer('actions', function (Options $resolver, array $actions) {
                $actionsOptionsResolver = new OptionsResolver();
                $actionsOptionsResolver->setDefined('action')
                    ->setRequired('action')
                    ->setAllowedValues('action', ['create', 'delete', 'move', 'update'])
                ;
                $actionsOptionsResolver->setDefined('file_path')
                    ->setRequired('file_path')
                ;
                $actionsOptionsResolver->setDefined('previous_path');
                $actionsOptionsResolver->setDefined('content');
                $actionsOptionsResolver->setDefined('encoding')
                    ->setAllowedValues('encoding', ['text', 'base64'])
                ;

                return array_map(function ($action) use ($actionsOptionsResolver) {
                    return $actionsOptionsResolver->resolve($action);
                }, $actions);
            })
        ;
        $resolver->setDefined('author_email');
        $resolver->setDefined('author_name');

        return $this->post($this->getProjectPath($project_id, 'repository/commits'), $resolver->resolve($parameters));
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     * @param array      $parameters
     *
     * @return mixed
     */
    public function commitComments($project_id, $sha, array $parameters = [])
    {
        $resolver = $this->createOptionsResolver();

        return $this->get(
            $this->getProjectPath($project_id, 'repository/commits/'.self::encodePath($sha).'/comments'),
            $resolver->resolve($parameters)
        );
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     * @param string     $note
     * @param array      $params
     *
     * @return mixed
     */
    public function createCommitComment($project_id, $sha, $note, array $params = [])
    {
        $params['note'] = $note;

        return $this->post($this->getProjectPath($project_id, 'repository/commits/'.self::encodePath($sha).'/comments'), $params);
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     * @param array      $params
     *
     * @return mixed
     */
    public function getCommitBuildStatus($project_id, $sha, array $params = [])
    {
        return $this->get($this->getProjectPath($project_id, 'repository/commits/'.self::encodePath($sha).'/statuses'), $params);
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     * @param string     $state
     * @param array      $params
     *
     * @return mixed
     */
    public function postCommitBuildStatus($project_id, $sha, $state, array $params = [])
    {
        $params['state'] = $state;

        return $this->post($this->getProjectPath($project_id, 'statuses/'.self::encodePath($sha)), $params);
    }

    /**
     * @param int|string $project_id
     * @param string     $fromShaOrMaster
     * @param string     $toShaOrMaster
     * @param bool       $straight
     *
     * @return mixed
     */
    public function compare($project_id, $fromShaOrMaster, $toShaOrMaster, $straight = false)
    {
        return $this->get($this->getProjectPath(
            $project_id,
            'repository/compare?from='.self::encodePath($fromShaOrMaster).'&to='.self::encodePath($toShaOrMaster).'&straight='.self::encodePath($straight ? 'true' : 'false')
        ));
    }

    /**
     * @param int|string $project_id
     * @param string     $sha
     *
     * @return string
     */
    public function diff($project_id, $sha)
    {
        return $this->get($this->getProjectPath($project_id, 'repository/commits/'.self::encodePath($sha).'/diff'));
    }

    /**
     * @param int|string $project_id
     * @param array      $params
     *
     * @return mixed
     */
    public function tree($project_id, array $params = [])
    {
        return $this->get($this->getProjectPath($project_id, 'repository/tree'), $params);
    }

    /**
     * @param int|string $project_id
     *
     * @return mixed
     */
    public function contributors($project_id)
    {
        return $this->get($this->getProjectPath($project_id, 'repository/contributors'));
    }

    /**
     * @param int|string $project_id
     * @param array      $params
     * @param string     $format     Options: "tar.gz", "zip", "tar.bz2" and "tar"
     *
     * @return mixed
     */
    public function archive($project_id, $params = [], $format = 'tar.gz')
    {
        return $this->get($this->getProjectPath($project_id, 'repository/archive.'.$format), $params);
    }

    /**
     * @param int|string $project_id
     * @param array      $refs
     *
     * @return mixed
     */
    public function mergeBase($project_id, $refs)
    {
        return $this->get($this->getProjectPath($project_id, 'repository/merge_base'), ['refs' => $refs]);
    }

    protected function createOptionsResolver()
    {
        $allowedTypeValues = [
            self::TYPE_BRANCH,
            self::TYPE_TAG,
        ];

        $resolver = parent::createOptionsResolver();
        $resolver->setDefined('type')
            ->setAllowedTypes('type', 'string')
            ->setAllowedValues('type', $allowedTypeValues);

        return $resolver;
    }
}