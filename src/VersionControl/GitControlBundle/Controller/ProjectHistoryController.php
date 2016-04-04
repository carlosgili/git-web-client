<?php

namespace VersionControl\GitControlBundle\Controller;

use VersionControl\GitControlBundle\Controller\Base\BaseProjectController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use VersionControl\GitControlBundle\Entity\Project;
use VersionControl\GitControlBundle\Form\ProjectType;
use VersionControl\GitControlBundle\Utility\GitCommands;
use Symfony\Component\Validator\Constraints\NotBlank;
use VersionControl\GitControlBundle\Entity\UserProjects;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use VersionControl\GitControlBundle\Annotation\ProjectAccess;

/**
 * Project controller.
 *
 * @Route("/project/{id}/history")
 */
class ProjectHistoryController extends BaseProjectController
{
    /**
     *
     * @var GitCommand 
     */
    protected $gitLogCommand;
    
    
    /**
     * Displays the project commit history for the current branch.
     *
     * @Route("/", name="project_log")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="VIEW")
     */
    public function listAction(Request $request,$id)
    {
        
        $currentPage = $request->query->get('page', 1); 
        $filter = false;
        //Search
        /*$keyword = $request->query->get('keyword', false);
        $filter= $request->query->get('filter', false);
        if($keyword !== false && trim($keyword) !== ''){
            if($filter !== false){
                if($filter === 'author'){
                    $this->gitLogCommand->setFilterByAuthor($keyword);
                }elseif($filter === 'content'){
                    $this->gitLogCommand->setFilterByContent($keyword);
                }else{
                    $this->gitLogCommand->setFilterByMessage($keyword);
                }
            }
        }*/
        
        $searchForm = $this->createSearchForm();
        $searchForm->handleRequest($request);
        
        if ($searchForm->isValid()) {
            $data = $searchForm->getData();
            
            $keyword = $data['keyword'];
            $filter = $data['filter'];
            $branch = $data['branch'];
            
            if($keyword !== false && trim($keyword) !== ''){
                if($filter !== false){
                    if($filter === 'author'){
                        $this->gitLogCommand->setFilterByAuthor($keyword);
                    }elseif($filter === 'content'){
                        $this->gitLogCommand->setFilterByContent($keyword);
                    }else{
                        $this->gitLogCommand->setFilterByMessage($keyword);
                    }
                }
            }
            
             $this->gitLogCommand->setBranch($branch)
                ->setPage(($currentPage-1));
             
        }else{
             $this->gitLogCommand->setBranch($this->branchName)
                ->setPage(($currentPage-1));
        }
        
        $gitLogs = $this->gitLogCommand->execute()->getResults();

        return array_merge($this->viewVariables, array(

            'gitLogs' => $gitLogs,
            'totalCount' => $this->gitLogCommand->getTotalCount(),
            'limit' => $this->gitLogCommand->getLimit(),
            'currentPage' => $this->gitLogCommand->getPage()+1,
            //'keyword' => $keyword,
            'filter' => $filter,
            'searchForm' => $searchForm->createView()
        ));
    }
    
    /**
     * Show Git commit diff
     *
     * @Route("/commit/{commitHash}", name="project_commitdiff")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="VIEW")
     */
    public function commitHistoryAction($id,$commitHash){
        
        
        $gitDiffCommand = $this->gitCommands->command('diff');

        $this->gitLogCommand
                ->setLogCount(1)
                ->setCommitHash($commitHash);
        
        //$gitLog = $this->gitFilesCommands->getCommitLog($commitHash,$this->branchName);
        $gitLog = $this->gitLogCommand->execute()->getFirstResult();
        
        //Get git Diff
        //$gitDiffs = $gitDiffCommand->getCommitDiff($commitHash);
        $files = $gitDiffCommand->getFilesInCommit($commitHash);

        
        return array_merge($this->viewVariables, array(
            'log' => $gitLog,
            //'diffs' => $gitDiffs,
            'files' => $files,
        ));
    }
    
    /**
     * Show Git commit diff
     *
     * @Route("/commitfile/{commitHash}/{filePath}", name="project_commitfilediff")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="VIEW")
     */
    public function fileDiffAction($id,$commitHash,$filePath){
        
        
        $gitDiffCommand = $this->gitCommands->command('diff');

        $difffile = urldecode($filePath);
        
        $previousCommitHash = $gitDiffCommand->getPreviousCommitHash($commitHash);
        
        $gitDiffs = $gitDiffCommand->getDiffFileBetweenCommits($difffile,$previousCommitHash,$commitHash);
   
        return array_merge($this->viewVariables, array(
            'diffs' => $gitDiffs,
        ));
    }
    
    /**
     * Show Git commit diff
     *
     * @Route("/checkout-file/{commitHash}/{filePath}", name="project_checkout_file")
     * @Method("GET")
     * @ProjectAccess(grantType="VIEW")
     */
    public function checkoutFileAction($id,$commitHash,$filePath){
        
        
        $gitUndoCommand = $this->gitCommands->command('undo');

        $file = urldecode($filePath);
        
        $response = $gitUndoCommand->checkoutFile($file,$commitHash);
        
        $this->get('session')->getFlashBag()->add('notice', $response);
        $this->get('session')->getFlashBag()->add('warning', "Make sure to commit the changes.");
            
        return $this->redirect($this->generateUrl('project_commitdiff', array('id' => $id,'commitHash' => $commitHash)));
       
    }
    
    /**
     * 
     * @param integer $id Project Id
     */
    public function initAction($id, $grantType = 'VIEW'){
 
        parent::initAction($id,$grantType);
        
        $this->gitLogCommand = $this->gitCommands->command('log');
 
    }
    
    /**
    * Creates a form to edit a Project entity.
    *
    * @param Project $project The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createSearchForm()
    {

        //$remoteChoices = array();
        //foreach($gitRemoteVersions as $remoteVersion){
        //    $remoteChoices[$remoteVersion[0]] = $remoteVersion[0].'('.$remoteVersion[1].')'; 
        //}
        
        //Local Branch choice
        $branches = $this->gitCommands->command('branch')->getBranches(true);
        $branchChoices = array();
        foreach($branches as $branchName){
            $branchChoices[$branchName] = $branchName;
        }
               
        //Current branch
        $currentBranch = $this->gitCommands->command('branch')->getCurrentBranch();
        
        
        $defaultData = array('branch' => $currentBranch);
        $form = $this->createFormBuilder($defaultData, array(
                'action' => $this->generateUrl('project_log', array('id' => $this->project->getId())),
                'method' => 'GET',
            ))
            ->add('branch', 'choice', array(
                'label' => 'Branch'
                ,'choices'  => $branchChoices
                ,'preferred_choices' => array($currentBranch)
                ,'data' => trim($currentBranch)
                ,'required' => false
                ,'constraints' => array(
                    //new NotBlank()
                ))
            )   
            ->add('filter','hidden')
            ->add('keyword','text',array('required' => false))
            ->getForm();

        //$form->add('submitMain', 'submit', array('label' => 'Push'));
        return $form;
    }
}