<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\UserType;
use AppBundle\Form\LocationType;
use AppBundle\Form\BlogType;
use AppBundle\Entity\Blog;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class WSardiniaController extends Controller
{
    
    /**
     * @Route("/profile/dashboard", name= "dashboard"  )
     */
    public function edit_profileAction(Request $request)
    {
        
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isValid()) {

            /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */

            $userManager = $this->get('fos_user.user_manager');

            $event = new FormEvent($form, $request);

            $dispatcher = $this->get('event_dispatcher');

            $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {

                //$url = $this->generateUrl('fos_user_profile_show');
               
                $url = $this->generateUrl('dashboard');
                $response = new RedirectResponse($url);

            }
            $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

             $this->get('session')->getFlashBag()->add(
                                            'notice',
                                            'Profile Updated!'
                );

            return $response;
        }

        
        $locations = $user->getLocation();
        $reviews = $user->getReview();
        return $this->render('default/user_profile.html.twig',array('form'=>$form->createView(),'locations'=>$locations, 'reviews'=>$reviews));
    }

    /**
    * @Route("/", name="homepage")
    */
    public function showLatestLocationAction(Request $request)
    {
        $searchQuery = $request->get('query');
        
        if(!empty($searchQuery)){
            $finder = $this->container->get('fos_elastica.finder.app.location');
            $users = $finder->find($searchQuery);
            dump($users);
        }

        $em = $this->getDoctrine()->getManager();
        $locations = $em->getRepository('AppBundle:Location')->findLatest(4);
        return $this->render('default/index.html.twig', array('lastLocations'=>$locations));
    }

    /**
     * @Route("/discover", name= "discover"  )
     */
    public function showMarkersAction()
    {
        $em = $this->getDoctrine()->getManager();
        $locations = $em->getRepository('AppBundle:Location')->findLatLng(); 
        
        $json = json_encode($locations);
        
        return $this->render('default/discover.html.twig', array('locations'=>$json));

    }

    /**
     * @Route("/blog", name= "blog"  )
     */
    public function blogAction(Request $request)
    {
        
        $em = $this->getDoctrine()->getManager();
        //$blog = $em->getRepository('AppBundle:Blog')->findAll();

        $dql = "SELECT b FROM AppBundle:Blog b ORDER BY b.datePost DESC";
        $blogs = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $blogs, $request->query->getInt('page', 1),3
        );

        return $this->render('default/blog_loop.html.twig', array('pagination'=>$pagination));

    }

}