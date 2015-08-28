<?php

namespace VersionContol\GitControlBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use VersionContol\GitControlBundle\Entity\Project;
use VersionContol\GitControlBundle\Form\RegistrationType;
use VersionContol\GitControlBundle\Form\EditUserType;
use VersionContol\GitControlBundle\Utility\GitCommands;
use Symfony\Component\Validator\Constraints\NotBlank;
use VersionContol\GitControlBundle\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Project controller.
 * @Method("GET")
 * @Route("/user-managerment")
 * @Security("has_role('ROLE_ADMIN')")
 */
class UserManagementController extends Controller
{
    
    /**
     * @Route("/", name="usermanagement")
     * @Template()
     */
    public function listAction()
    {
        $userManager = $this->get('fos_user.user_manager');
        $users = $userManager->findUsers();
        
         return array(
            'users' => $users,
        );
    }
    
    /**
     * Creates a new Project entity.
     *
     * @Route("/", name="usermanagement_create")
     * @Method("POST")
     * @Template("VersionContolGitControlBundle:UserManagement:new.html.twig")
     */
    public function createAction(Request $request)
    {

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();
        
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $userManager->updateUser($user);

            $this->get('session')->getFlashBag()->add('notice', 'A new user has been created');
            
            return $this->redirect($this->generateUrl('usermanagement'));
        }

        return array(
            'user' => $user,
            'form'   => $form->createView(),
        );
    }
        
    /**
     * Displays a form to create a new user entity.
     *
     * @Route("/new", name="usermanagement_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $form   = $this->createCreateForm($user);

        return array(
            'user' => $user,
            'form'   => $form->createView(),
        );
    }


    /**
     * Creates a form to create a User entity.
     *
     * @param User $user The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(User $user)
    {
        $form = $this->createForm(new RegistrationType(), $user, array(
            'action' => $this->generateUrl('usermanagement_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }
    
    /**
     * Displays a form to edit an existing Project entity.
     *
     * @Route("/{id}/edit", name="usermanagement_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $userManager = $this->get('fos_user.user_manager');
        
        //$em = $this->getDoctrine()->getManager();
        //$user = $em->getRepository('VersionContolGitControlBundle:User/User')->find($id);

        //$user = $userManager->find($id);
        $user = $userManager->findUserBy(array('id' => $id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user entity.');
        }

        $editForm = $this->createEditForm($user);

        return array(
            'user'      => $user,
            'edit_form'   => $editForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Project entity.
    *
    * @param Project $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(User $entity)
    {
        $form = $this->createForm(new EditUserType(), $entity, array(
            'action' => $this->generateUrl('usermanagement_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Project entity.
     *
     * @Route("/{id}", name="usermanagement_update")
     * @Method("PUT")
     * @Template("VersionContolGitControlBundle:UserManagement:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id' => $id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user entity.');
        }
        
        $editForm = $this->createEditForm($user);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $userManager->updateUser($user);

            $this->get('session')->getFlashBag()->add('notice', 'User has been updated');
            
            return $this->redirect($this->generateUrl('usermanagement'));
        }

        return array(
            'user'      => $user,
            'edit_form'   => $editForm->createView(),
        );
    }
}