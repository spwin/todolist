<?php

namespace TodoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use TodoBundle\Entity\Job;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        $user_session_id = 0;
        if($session->has('user_id')){
            $user_session_id = $session->get('user_id');
        } else {
            $query = $em->getRepository('TodoBundle:Job');
            $max_user_id = $query->findBy(array(), array('userId'=>'desc'), 1);
            $user_session_id = $max_user_id[0]->getUserId()+1;
            $session->set('user_id', $user_session_id);
        }
        
        $todoList = $em->getRepository('TodoBundle:Job')->findBy(
                array('userId' => $user_session_id)
                );
        
        $job = new Job();
        $form = $this->createFormBuilder($job)
                ->add('description', 'textarea')
                ->add('save', 'submit', array('label'=>'Save'))
                ->getForm();
        
        return array('list'=>$todoList, 'form'=>$form->createView());
    }
    
    /**
     * @Route("/add-job", name="add_job")
     * @Template()
     */
    public function addAction(Request $request)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        
        $data = $request->request->all();
        
        $job = new Job();
        $job->setDescription($data['form']['description']);
        $job->setTimestamp(time());
        $job->setUserId($session->get('user_id'));
        
        $em->persist($job);
        $em->flush();
        
        $data['form']['id'] = $job->getId();
        
        return new Response(json_encode($data),200);
    }
    
    /**
     * @Route("/del-job", name="del_job")
     * @Template()
     */
    public function delAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $data = $request->request->all();
        $id = $data['id'];
        
        $job = $em->getRepository('TodoBundle:Job')->find($id);
        
        $em->remove($job);
        $em->flush();
        
        return new Response(json_encode($data),200);
    }
    
    /**
     * @Route("/get-all", name="get_all")
     * @Template()
     */
    public function getAction(Request $request)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();

        $user_session_id = $session->get('user_id');
        $todoList = $em->getRepository('TodoBundle:Job')->findBy(
               array('userId' => $user_session_id)
               );
        $data = array();
        foreach($todoList as $list){
            $data[] = array('id'=>$list->getId(), 'user_id'=>$list->getUserId(), 'description'=>$list->getDescription());
        }
        
        return new Response(json_encode($data),200);
    }
}
