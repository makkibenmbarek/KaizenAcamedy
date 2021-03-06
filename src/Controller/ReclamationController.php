<?php

namespace App\Controller;

use App\Entity\MessageReclam;
use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ReclamationController extends AbstractController
{
    /**
     * @Route("/reclamation/", name="reclamations")
     */
    public function index(): Response
    {
        $connectedId = 1;
        $adminId=2;
        $em=$this->getDoctrine()->getManager();
        $reclamations=$em->getRepository(Reclamation::class)->findBy(
            ['id_user' => $connectedId]
        );
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'connectedId' => $connectedId,
            'adminId' => $adminId
        ]);
    }

    /**
     * @Route("/reclamation/add", name="addReclamation")
     */
    public function addReclamation(Request $request): Response
    {
        $reclamation=new Reclamation();
        $reclamation->setIdUser(1);
        $Form=$this->createForm(ReclamationType::class,$reclamation);
        $Form->handleRequest($request);

        if ($Form->isSubmitted()&&$Form->isValid())/*verifier */
        {
            $em=$this->getDoctrine()->getManager();
            $em->persist($reclamation);
            $em->flush();
            return $this->redirectToRoute('reclamations');
        }
        return $this->render('reclamation/add.html.twig', array(
            'reclamationform'=>$Form->createView(),
        ));
    }

    /**
     * @Route("/reclamation/Delete/{idReclamation}", name="delete_Reclamation")
     */
    public function DeleteReclamation($idReclamation):Response
    {

        $em=$this->getDoctrine()->getManager();
        $reclamation=$em->getRepository(Reclamation::class)->find($idReclamation);

        $em->remove($reclamation);
        $em->flush();
        return $this->redirectToRoute('reclamations');
    }

    /**
     * @Route("/reclamation/Update/{idReclamation}", name="update_Reclamation")
     */
    public function UpdateReclamation($idReclamation,Request $request):Response
    {
        $em=$this->getDoctrine()->getManager();
        $reclamation=$em->getRepository(Reclamation::class)->find($idReclamation);
        $Form=$this->createForm(ReclamationType::class,$reclamation);
        $Form->handleRequest($request);
        if ($Form->isSubmitted())
        {
            $em->flush();
            return $this->redirectToRoute('reclamations');
        }
        return $this->render('reclamation/edit.html.twig', array(
            'reclamationform'=>$Form->createView()
        ));
    }




    /*Chat*/
    /**
     * @Route("/reclamation/AddMessage/{idReclamation}/{idReceiver}/{msg}", name="AddMessage")
     */
    public function AddMessageAction($idReclamation,$idReceiver,$msg){
        $connectedId=1;
        $em = $this->getDoctrine()->getManager();

        $message=new MessageReclam();
        $message->setDate(new \DateTime());
        $message->setIdSend($connectedId);
        $message->setIdReceiver($idReceiver);
        $message->setReclamation($idReclamation);
        $message->setMessage($msg);

        $em->persist($message);
        $em->flush();

        return new JsonResponse("added");
    }

    /**
     * @Route("/reclamation/AddMessageAdmin/{idReclamation}/{idReceiver}/{msg}", name="AddMessageAdmin")
     */
    public function AddMessageAdminAction($idReclamation,$idReceiver,$msg){
        $adminId=2;
        $em = $this->getDoctrine()->getManager();

        $message=new MessageReclam();
        $message->setDate(new \DateTime());
        $message->setIdSend($adminId);
        $message->setIdReceiver($idReceiver);
        $message->setReclamation($idReclamation);
        $message->setMessage($msg);

        $em->persist($message);
        $em->flush();

        return new JsonResponse("added");
    }


    /**
     * @Route("/reclamation/getMessages/{idReceiver}/{idReclamation}", name="getMessages")
     */
    public function getMessagesAction($idReceiver,$idReclamation){
        $connectedId=1;
        $serializer = new Serializer([new ObjectNormalizer()]);
        $em = $this->getDoctrine()->getManager();
        $result = $em->getRepository(MessageReclam::class)->getMessages($connectedId,$idReceiver,$idReclamation);
        if(empty($result)){//didnt find any messages so trying in reverse
            $result = $em->getRepository(MessageReclam::class)->getMessages($idReceiver,$connectedId,$idReclamation);
            $messages=$serializer->normalize($result);
            return new JsonResponse($messages);
        }else{
            $secondMessages=$em->getRepository(MessageReclam::class)->getMessages($idReceiver,$connectedId,$idReclamation);
            $globalMessages=array_merge($result,$secondMessages);//combine messages



            usort($globalMessages, function($a, $b) {
                $firstDate = $a->getDate()->format('Y-m-d H:i:s');
                $secondDate = $b->getDate()->format('Y-m-d H:i:s');
                return strcmp($firstDate, $secondDate);
            });
            $messages=$serializer->normalize($globalMessages);

            return new JsonResponse($messages);

    }
    }

}
