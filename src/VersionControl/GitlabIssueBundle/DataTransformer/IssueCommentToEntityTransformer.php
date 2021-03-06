<?php
/*
 * This file is part of the GitlabIssueBundle package.
 *
 * (c) Paul Schweppe <paulschweppe@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VersionControl\GitlabIssueBundle\DataTransformer;

use VersionControl\GitlabIssueBundle\Entity\Issues\IssueComment;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IssueCommentToEntityTransformer implements DataTransformerInterface
{
    private $userTransformer;

    public function __construct()
    {
        $this->userTransformer = new UserToEntityTransformer();
    }

    /**
     * Transforms an issue array into an issue Entity object.
     *
     * @param \VersionControl\GitlabIssueBundle\Entity\Issues\IssueComment|null $issueComment
     *
     * @return string
     */
    public function transform($issueComment)
    {
        if (null === $issueComment) {
            return null;
        }

        $issueCommentEntity = new IssueComment();
        $issueCommentEntity->setId($issueComment['id']);
        $issueCommentEntity->setComment($issueComment['body']);
        $issueCommentEntity->setCreatedAt($this->formatDate($issueComment['created_at']));
        if (isset($issueComment['updated_at'])) {
            $issueCommentEntity->setUpdatedAt($this->formatDate($issueComment['updated_at']));
        }

        if (isset($issueComment['author']) && is_array($issueComment['author'])) {
            $user = $this->userTransformer->transform($issueComment['author']);
            $issueCommentEntity->setUser($user);
        }

        return $issueCommentEntity;
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param VersionControl\GitlabIssueBundle\Entity\Issues\IssueComment $issueCommentEntity
     *
     * @return array|null
     *
     * @throws TransformationFailedException if object (issue) is not found
     */
    public function reverseTransform($issueCommentEntity)
    {
        if ($issueCommentEntity === null) {
            // causes a validation error
            throw new TransformationFailedException('issueCommentEntity is null');
        }

        $issueComment = array(
            'body' => $issueCommentEntity->getComment(),
        );

        return $issueComment;
    }

    protected function formatDate($date)
    {
        try {
            $dateTime = new \DateTime($date);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return $dateTime;
    }
}
