<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\Car;
use App\Entity\Setting;
use App\Entity\User;
use App\Form\Admin\MessagesType;
use App\Form\UserType;
use App\Repository\Admin\CommentRepository;
use App\Repository\CarRepository;
use App\Repository\ImageRepository;
use App\Repository\SettingRepository;
use PhpParser\Node\Stmt\Return_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository,CarRepository $carRepository)
    {
        $setting=$settingRepository->findAll();
        // $slider=$carRepository->findAll();
        $slider=$carRepository->findBy([],['title'=>'ASC'],3);
        $cars=$carRepository->findBy([],['title'=>'DESC'],5);
        $car1=$carRepository->findBy([],['title'=>'DESC'],1);
        $newcars=$carRepository->findBy([],['title'=>'DESC'],10);


        //array findBy(array $criteria, array $orderBy=null, int |null $limit=null, int|null $offset=null)
        return $this->render('home/index.html.twig', [

            'controller_name' => 'HomeController',
            'setting'=>$setting,
            'slider'=>$slider,
            'cars'=>$cars,
            'car1'=>$car1,
            'newcars'=>$newcars,

        ]);
    }
    /**
     * @Route("/cars/{id}", name="car_show", methods={"GET"})
     */
    public function show(Car $car,$id, ImageRepository $imageRepository,CommentRepository $commentRepository): Response
    {

        $images=$imageRepository->findBy(['car'=>$id]);
        $comments=$commentRepository->findBy(['carid'=>$id]);
        //dump($comments);
       // die();
        return $this->render('home/carshow.html.twig', [
            'car' => $car,
            'images' => $images,
            'comments' => $comments,
        ]);
    }

    /**
     * @Route("about", name="home_about", methods={"GET"})
     */
    public function about(SettingRepository $settingRepository): Response
    {
        $setting=$settingRepository->findAll();
        return $this->render('home/aboutus.html.twig', [
            'setting'=>$setting,
        ]);
    }

    /**
     * @Route("contact", name="home_contact", methods={"GET","POST"})
     */
    public function contact(SettingRepository $settingRepository,Request $request): Response
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);
        $submittedToken = $request->request->get('token');

        $setting=$settingRepository->findAll(); //get setting data
        //   dump($request);
        //     die();

        if ($form->isSubmitted()) {
            if($this->isCsrfTokenValid('form-message',$submittedToken)){


                $entityManager = $this->getDoctrine()->getManager();
                $message->setStatus('New');
                $message->setIp($_SERVER['REMOTE_ADDR']);
                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success', 'Mesajınız başarıyla gönderilmiştir');

                //*********** SEND EMAIL ***********************>>>>>>>>>>>>>>
                $email= (new Email())
                    ->from($setting[0]->getSmtpemail())
                    ->to($form['email']->getData())
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)

                    ->subject('Car')
                    //->text('Sending emails is fun again!')

                    ->html("Dear ".$form['name']->getData() ."<br>
                        <p>we will evaluate your reqests and contact you as soon as possible</p>
                        Thank You for your message<br>
                        ==================================
                        <br>".$setting[0]->getCompany()." <br>
                        Adress :  ".$setting[0]->getAddress()." <br>
                        Phone  :  ".$setting[0]->getPhone()." <br>"
                    );
                $transport = new GmailTransport($setting[0]->getSmtpemail(), $setting[0]->getSmtppassword());
                $mailer =new Mailer($transport);
                $mailer->send($email);
                //<<<<<<<<<<<<<<<<<<<<<<<*****************************SEND EMAİL*************************


                return $this->redirectToRoute('home_contact');
            }
        }
        $setting=$settingRepository->findAll();
        return $this->render('home/contact.html.twig', [
            'setting'=>$setting,
            'form' => $form->createView(),
        ]);
    }



    /**
     * @Route("/redirector", name="redirector")
     */
    public function redirector()
    {
        if($this->getUser())
        {
            if($this->getUser()->getRoles()[0] == "ROLE_ADMIN" )
            {
                return $this->redirectToRoute('admin_admin');
            }else
                return $this->redirectToRoute('home');
        }
        return $this->redirectToRoute('home');

    }
    /**
     * @Route("/register", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request,UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $entityManager = $this->getDoctrine()->getManager();
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $user->setRoles(array($request->request->get('user')['roles'], ));

            //dump($user);
            $file = $request->files->get('user')['image'];
            if($file)
            {

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $newname = uniqid() . $originalFilename .'.'.$file->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $file->move(
                        $this->getParameter('upload_directory'),
                        $newname
                    );
                } catch (FileException $e) {
                    dump("Something went wrong");
                    die();
                }
                $user->setImage($newname);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('user_index');
            }else{
                dump("Couldnt move");
                die();
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
