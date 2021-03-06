<?php

namespace AppBundle\Controller\Web;

use AppBundle\Entity\Task;
use AppBundle\Form\TaskFormType;
use AppBundle\Includes\Constants;
use AppBundle\Includes\RoleEnums;
use AppBundle\Includes\StatusEnums;
use AppBundle\Includes\TypeEnums;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TaskController
 *
 * @Route("/tasks")
 * @package AppBundle\Controller
 */
class TaskController extends Controller
{
    /**
     * @Route("/", name="task_list")
     *
     * @param $request Request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

//        $tasks = $em->getRepository('AppBundle:Task')
//            ->findBy([
//                'createdBy' => $this->get('user_service')->getEntitledUsers(),
//            ]);

        $queryBuilder = $em->getRepository('AppBundle:Task')->createQueryBuilder('t');

        if (in_array(RoleEnums::SuperAdmin, $this->getUser()->getRoles())) {
            $query = $queryBuilder
                ->orderBy('t.createdBy')
                ->getQuery();
        } else {
            $query = $queryBuilder
                ->Where('t.createdBy = :user_id')
                ->setParameter('user_id', $this->getUser()->getId())
                ->orderBy('t.name')
                ->getQuery();
        }

        $paginator = $this->get('knp_paginator');

        $tasks = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), //page number
            Constants::ROWS_PER_PAGE //limit per page
        );

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks
        ]);
    }

    /**
     * @Route("/{id}/edit", name="task_edit")
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id)
    {
        $em = $this->getDoctrine()->getManager();

        $task = $em->getRepository('AppBundle:Task')
            ->findOneBy([
                'id' => $id,
                'createdBy' => $this->get('user_service')->getEntitledUsers(),
            ]);

        $form = $this->createForm(TaskFormType::class, $task);

        // only handles data on POST
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $task = $form->getData();
            $task->setModifiedAt(new \DateTime('now'));
            $task->setModifiedBy($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'taskForm' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * @Route("/new", name="task_new")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $task = new Task();

        $em = $this->getDoctrine()->getManager();

        // Check if we need to pre-populate with a Task
        if (null !== $request->query->get('category')) {
            $category = $em->getRepository('AppBundle:Category')
                ->findOneBy([
                    'id' => $request->query->get('category'),
                    'createdBy' => $this->get('user_service')->getEntitledUsers(),
                ]);
            $task->setCategory($category);
        }

        $form = $this->createForm(TaskFormType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $task = $form->getData();

            $task->setCreatedAt(new \DateTime('now'));
            $task->setModifiedAt(new \DateTime('now'));
            $task->setCreatedBy($this->getUser());
            $task->setModifiedBy($this->getUser());
            $task->setStatus(StatusEnums::Active);
            $task->setType('Custom');

            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/new.html.twig', [
            'taskForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/newAjax", name="task_new_ajax")
     *
     * @param $request Request
     *
     * @return Response
     */
    public function newAjaxAction(Request $request)
    {
        try {
            $form = $this->createForm(TaskFormType::class, new Task(), [
                'hideSubmit' => true,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();

                    $task = $form->getData();

                    $task->setCreatedAt(new \DateTime('now'));
                    $task->setModifiedAt(new \DateTime('now'));
                    $task->setCreatedBy($this->getUser());
                    $task->setModifiedBy($this->getUser());
                    $task->setType(TypeEnums::Custom);
                    $task->setStatus(StatusEnums::Active);

                    $em->persist($task);
                    $em->flush();

                    //all good, category saved
                    $response['success'] = true;
                    $response['message'] = 'Task added.';
                    $response['taskId'] = $task->getId();
                    $response['taskName'] = $task->getName();

                    return new JsonResponse($response, 200);

                } else {
                    //invalid form
                    $response['success'] = false;
                    $response['message'] = 'Form not valid.';

                    return new JsonResponse($response, 400);
                }
            }

            //return value of the GET request
            return $this->render('task/_form.html.twig', [
                'taskForm' => $form->createView()
            ]);

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();

            return new JsonResponse($response, 500);
        }
    }

    /**
     * @Route("/{id}", name="task_show")
     * @param $id
     * @Method("GET")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id)
    {
        $task = null;

        $em = $this->getDoctrine()->getManager();

        $task = $em->getRepository('AppBundle:Task')
            ->findOneBy([
                'id' => $id,
                'createdBy' => $this->get('user_service')->getEntitledUsers(),
            ]);

        if (!$task) {
            throw $this->createNotFoundException(
                'No task found for id ' . $id
            );
        }

//        $fields = $em->getRepository('AppBundle:TaskField')
//            ->findByTask($id);

        return $this->render('task/show.html.twig', [
            'task' => $task,
//            'fields' => $fields,
        ]);
    }

    /**
     * @Route("/{id}", name="task_delete")
     * @param $request
     * @param $id
     * @Method("DELETE")
     * @return Response
     */
    public function deleteAction(Request $request, $id)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $task = $em->getRepository('AppBundle:Task')
                ->findOneBy([
                    'id' => $id,
                    'status' => StatusEnums::Active,
                    'createdBy' => $this->get('user_service')->getEntitledUsers(),
                ]);

            if (!$task) {
                throw $this->createNotFoundException('Unable to find Task entity.');
            }

            // Check if the task is in use on any task
            $servicesByTask = $em->getRepository('AppBundle:Service')
                ->findBy([
                    'task' => $task
                ]);

            if (count($servicesByTask) == 0) {
                // Safe to remove
                $task->setStatus(StatusEnums::Deleted);
                $task->setModifiedBy($this->getUser());

                $em->persist($task);
                $em->flush();

                $response['success'] = true;
                $response['message'] = 'Task deleted.';
            } else {
                $response['success'] = false;
                $response['message'] = 'This Task is in use on some Services. Delete its references first.';
            }
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        return new JsonResponse($response);
    }

}
